let path = require('path'), fs = require('fs'), crypto = require('crypto');

let BuildableFile = function (filePath, extension, moduleName, needsRebuild, skipFileSteps) {
    this.path = filePath;
    this.extension = extension !== undefined ? extension : path.extname(filePath);
    this.moduleName = moduleName !== undefined ? moduleName : filePath;
    this.needsRebuild = needsRebuild !== undefined ? needsRebuild : true;
    this.skipFileSteps = skipFileSteps !== undefined ? skipFileSteps : false;
};

BuildableFile.fromData = function (data) {
    return new BuildableFile(data[0], data[1], data[2], data[3], data[4])
};

let File = function (name, module, content) {
    this.name = name;
    this.additionalFiles = [];

    this.update = function (newContent, moduleName) {
        if (newContent !== undefined) {
            if (!Buffer.isBuffer(newContent)) {
                throw new TypeError('content should be of type Buffer, got ' + (typeof newContent));
            }

            this.content = newContent;
        }

        if (moduleName !== undefined) {
            this.module = moduleName;
        }

        return this;
    };

    this.addAdditionalFile = function (outputFile, inputFiles) {
        this.additionalFiles.push({
            outputFile: outputFile,
            inputFiles: inputFiles.map((file) => new BuildableFile(file))
        });
    };

    this.update(content, module);
};

File.fromBuildFile = function (buildFile, content) {
    return new File(buildFile.path, buildFile.moduleName, content);
};

function mkdirRecursive(rootDir, pathToCreate) {
    let relativePath = path.relative(rootDir, pathToCreate);

    relativePath
        .split(path.sep)
        .reduce((currentPath, folder) => {
            currentPath = path.join(currentPath, folder);
            if (!fs.existsSync(currentPath)) {
                fs.mkdirSync(currentPath);
            }
            return currentPath;
        }, rootDir);
}

function getCachedFileLocation(cacheDir, rootDir, file) {
    let cleanPath = path.resolve(file);
    rootDir = path.resolve(rootDir);

    if (!cleanPath.startsWith(rootDir)) {
        throw new Error('Cannot get file "' + cleanPath + '" outside of the root.');
    }
    let hash = crypto.createHash('sha1').update(cleanPath.substr(rootDir.length).replace(path.sep, '|')).digest('hex');

    return {dir: path.join(cacheDir, hash.substr(0, 2)), file: hash.substr(2)};
}

function compileFile(buildableFile, config, logger) {
    // Check if there is a cache file.
    let hasCache = false;
    let cacheFile;

    // Do we want to skip the file steps and go straight to the module?
    if (buildableFile.skipFileSteps) {
        if (logger.isVerbose()) {
            logger.log(
                "Skipping build file steps for  \"" + buildableFile.path + "\".",
                {action: "FILE_INIT", file: buildableFile.path, metadata: {}}
            );
        }

        return new Promise(function (resolve, reject) {
            try {
                fs.readFile(buildableFile.path, function (err, buffer) {
                    if (err) reject(err); else resolve(File.fromBuildFile(buildableFile, buffer));
                });
            } catch (err) {
                reject(err);
            }
        });
    }

    if (config.paths.cache !== undefined) {
        cacheFile = getCachedFileLocation(config.paths.cache, config.paths.root, buildableFile.path);
        mkdirRecursive(config.paths.root, cacheFile.dir);

        hasCache = fs.existsSync(path.join(cacheFile.dir, cacheFile.file));
    }

    // Do we need a recompile or we do not have cache?
    if (!buildableFile.needsRebuild && hasCache) {
        return new Promise(function (resolve, reject) {
            try {
                if (logger.isVerbose()) {
                    logger.log(
                        "Reading from cache for \"" + buildableFile.path + "\".",
                        {action: "FILE_CACHE", file: buildableFile.path, metadata: {}}
                    );
                }

                fs.readFile(path.join(cacheFile.dir, cacheFile.file), function (err, buffer) {
                    if (err) reject(err); else resolve(File.fromBuildFile(buildableFile, buffer));
                });
            } catch (err) {
                reject(err);
            }
        });
    }

    let filePromise = new Promise(function (resolve, reject) {
        try {
            fs.readFile(buildableFile.path, function (err, buffer) {
                if (err) reject(err); else resolve(File.fromBuildFile(buildableFile, buffer));
            });
        } catch (err) {
            reject(err);
        }
    });

    if (config.build[buildableFile.extension] === undefined) {
        return Promise.reject('No build config for extension "' + buildableFile.extension + '".');
    }

    let steps = config.build[buildableFile.extension][0];

    if (logger.isVerbose()) {
        filePromise = filePromise.then((file) => {
            logger.log(
                "Initializing file build steps \"" + buildableFile.path + "\".",
                {action: "FILE_INIT", file: buildableFile.path, metadata: {}}
            );
            return file;
        });
    }
    for (let j = 0; j < steps.length; j++) {
        filePromise = filePromise.then(function (file) {
            return new Promise(function (resolve, reject) {
                try {
                    if (logger.isVerbose()) {
                        logger.log(
                            "Processing \"" + file.name + "\" file with \"" + steps[j] + "\".",
                            {action: "FILE_STEP", file: file.name, metadata: {step: steps[j]}}
                        );
                    }

                    resolve(require(steps[j])(file, config, (file) => compileFile(file, config, logger)));
                } catch (err) {
                    reject(err);
                }
            });
        });
    }

    if (config.paths.cache !== undefined) {
        // cache the result
        filePromise = filePromise.then(function (file) {
            return new Promise(function (resolve, reject) {
                try {
                    fs.writeFile(path.join(cacheFile.dir, cacheFile.file), file.content, function (err) {
                        if (err) reject(err); else resolve(file);
                    });
                } catch (err) {
                    reject(err);
                }
            });
        });
    }

    return filePromise;
}

function compileModule(outputFile, inputFiles, config, logger) {
    let modulePromises = [];

    // File steps
    for (let i = 0; i < inputFiles.length; i++) {
        modulePromises.push(compileFile(inputFiles[i], config, logger))
    }

    return Promise.all(modulePromises).then(function (moduleFiles) {
        let ext = path.extname(outputFile);
        let content = [];
        let writerPromises = [];

        for (let j = 0; j < moduleFiles.length; j++) {
            if (content.length > 0) {
                content.push(Buffer.from("\n"));
            }

            content.push(moduleFiles[j].content);

            // Build any additional files
            for (let k = 0; k < moduleFiles[j].additionalFiles.length; k++) {
                let additionalFile = moduleFiles[j].additionalFiles[k];
                if (logger.isVerbose()) {
                    logger.log(
                        "Compiling additional file \"" + additionalFile.outputFile + "\".",
                        {
                            action: "BUILD_ADDITIONAL",
                            file: path.join(config.paths.root, additionalFile.outputFile),
                            metadata: {parent: moduleFiles[j].name}
                        }
                    );
                }

                writerPromises.push(compileModule(
                    additionalFile.outputFile,
                    additionalFile.inputFiles,
                    config,
                    logger
                ));
            }
        }

        let steps = config.build[ext][1];
        let writers = config.build[ext][2];
        let moduleFile = new File(
            path.join(config.paths.root, outputFile),
            path.basename(outputFile, ext),
            Buffer.concat(content)
        );

        if (logger.isVerbose()) {
            logger.log(
                "Initializing module build steps \"" + moduleFile.name + "\".",
                {action: "MODULE_INIT", file: moduleFile.name, metadata: {}}
            );
        }

        for (let j = 0; j < steps.length; j++) {
            if (logger.isVerbose()) {
                logger.log(
                    "Processing \"" + moduleFile.name + "\" file with \"" + steps[j] + "\".",
                    {action: "MODULE_STEP", file: moduleFile.name, metadata: {step: steps[j]}}
                );
            }

            moduleFile = require(steps[j])(moduleFile, config);
        }


        mkdirRecursive(config.paths.root, path.dirname(moduleFile.name));

        for (let j = 0; j < writers.length; j++) {
            if (logger.isVerbose()) {
                logger.log(
                    "Writing \"" + moduleFile.name + "\" file with \"" + writers[j] + "\".",
                    {action: "WRITE_STEP", file: moduleFile.name, metadata: {step: writers[j]}}
                );
            }

            writerPromises.push(require(writers[j])(moduleFile, config));
        }

        return Promise.all(writerPromises).then(() => {
            // Make sure to log the write when all done.
            if (logger.isVerbose()) {
                logger.log(
                    "Done writing \"" + moduleFile.name + "\".",
                    {action: "WRITE", file: moduleFile.name, metadata: {}}
                );
            }
        })
    }, (e) => { logger.error(e); return Promise.reject(e); });
}

function compile(config, files, logger) {
    let outputFilePromises = [];

    for (let outputFile in files.input) {
        if (!files.input.hasOwnProperty(outputFile)) {
            continue;
        }

        outputFilePromises.push(compileModule(
            outputFile,
            files.input[outputFile].map((file) => BuildableFile.fromData(file)),
            config,
            logger
        ));
    }

    return Promise.all(outputFilePromises).catch((e) => { logger.error(e); return Promise.reject(e); });
}

function main(args, stdin, stdout) {
    return new Promise(function (resolve, reject) {
        // Parse the CLI arguments
        let isVerbose = false;
        let isDebug = false;
        let isStdIn = false;
        let logJson = false;
        let configFile = undefined;
        let filesFile = undefined;

        args.forEach(function (val, index) {
            if (val.startsWith('--')) {
                switch (val) {
                    case "--debug":
                        isVerbose = true;
                        isDebug = true;
                        break;
                    case "--verbose":
                        isVerbose = true;
                        break;
                    case "--stdin":
                        isStdIn = true;
                        break;
                    case "--log-json":
                        logJson = true;
                        break;
                }
            } else {
                if (!configFile) {
                    configFile = val;
                } else if (!filesFile) {
                    filesFile = val;
                }
            }
        });

        if (!configFile) {
            stdout.error("Missing config file.");
            reject("Missing config file.");
            return;
        }

        let config = {},
            files = {},
            logType = logJson ? 'json' : 'plain',
            logger = {
                log: (message, data) => {
                    if (logType === 'plain') {
                        stdout.log(message);
                    } else if (logType === 'json') {
                        stdout.log(JSON.stringify(data));
                    }
                },
                error: (e) => {
                    stdout.error(e)
                },
                isVerbose: () => {
                    return isVerbose;
                }
            };

        try {
            fs.accessSync(configFile, fs.constants.R_OK | fs.constants.W_OK);
            config = JSON.parse(fs.readFileSync(configFile));
        } catch (err) {
            stdout.error("Cannot read config file.");
            reject("Cannot read config file.");
            return;
        }

        if (filesFile) {
            try {
                fs.accessSync(filesFile, fs.constants.R_OK | fs.constants.W_OK);
                files = JSON.parse(fs.readFileSync(filesFile));
            } catch (err) {
                stdout.error("Cannot read files file.");
                reject("Cannot read files file.");
                return;
            }

            compile(config, files, logger)
                .then(() => resolve())
                .catch((e) => reject(e));
        } else if (isStdIn) {
            let content = '';

            // we need to split it on newlines so unicode characters can not be split in 2 data events.
            let stream = stdin.pipe(require('split')());
            stream.on('data', (buf) => content += buf.toString() + '\n');
            stream.on('end', () => {
                compile(config, JSON.parse(content), logger)
                    .then(() => resolve())
                    .catch((e) => reject(e));
            });
        } else {
            stdout.error("Cannot read stdin or files file.");
            reject("Cannot read stdin or files file.");
        }
    });
}

exports.main = main;
exports.File = File;

// CLI entry point
if (require.main === module) {
    const args = process.argv.slice(2);
    main(args, process.stdin, console)
        .then(() => process.exitCode = 0)
        .catch((e) => {
            console.error(e);
            process.exitCode = 1;
        });
}

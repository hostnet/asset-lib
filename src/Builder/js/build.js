let path = require('path'), fs = require('fs'), crypto = require('crypto');

function mkdirRecursive(rootDir, pathToCreate) {
    let relativePath = path.relative(rootDir, pathToCreate);

    relativePath
        .split(path.sep)
        .reduce((currentPath, folder) => {
            currentPath += folder + path.sep;
            try {
                fs.accessSync(currentPath, fs.constants.R_OK | fs.constants.W_OK);
            } catch (err) {
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
    let hash = crypto.createHash('sha1').update(cleanPath.substr(rootDir.length)).digest('hex');

    return {dir: cacheDir + hash.substr(0, 2) + path.sep, file: hash.substr(2)};
}

function compile(config, files, logger) {
    let outputFilePromises = [];

    for (let outputFile in files.input) {
        if (!files.input.hasOwnProperty(outputFile)) {
            continue;
        }

        let modulePromises = [];

        // File steps
        for (let i = 0; i < files.input[outputFile].length; i++) {
            let filePath = files.input[outputFile][i][0];
            let fileExtension = files.input[outputFile][i][1];
            let fileModuleName = files.input[outputFile][i][2];
            let fileNeedsRebuild = files.input[outputFile][i][3];
            let fileSkipFileSteps = files.input[outputFile][i][4];

            // Check if there is a cache file.
            let hasCache = false;
            let cacheFile;

            // Do we want to skip the file steps and go straight to the module?
            if (fileSkipFileSteps) {
                if (logger.isVerbose()) {
                    logger.log(
                        "Skipping build file steps for  \"" + filePath + "\".",
                        {action: "FILE_INIT", file: filePath, metadata: {}}
                    );
                }

                modulePromises.push(new Promise(function (resolve, reject) {
                    try {
                        fs.readFile(filePath, function (err, buffer) {
                            if (err) reject(err); else resolve({
                                name: filePath,
                                module: fileModuleName,
                                content: buffer
                            });
                        });
                    } catch (err) {
                        reject(err);
                    }
                }));

                continue;
            }

            if (config.paths.cache !== undefined) {
                cacheFile = getCachedFileLocation(config.paths.cache, config.paths.root, filePath);
                mkdirRecursive(config.paths.root, cacheFile.dir);

                try {
                    fs.accessSync(cacheFile.dir + cacheFile.file, fs.constants.R_OK | fs.constants.W_OK);
                    hasCache = true;
                } catch (err) {
                    hasCache = false;
                }
            }

            // Do we need a recompile or we do not have cache?
            if (!fileNeedsRebuild && hasCache) {
                modulePromises.push(new Promise(function (resolve, reject) {
                    try {
                        if (logger.isVerbose()) {
                            logger.log(
                                "Reading from cache for \"" + filePath + "\".",
                                {action: "FILE_CACHE", file: filePath, metadata: {}}
                            );
                        }

                        fs.readFile(cacheFile.dir + cacheFile.file, function (err, buffer) {
                            if (err) reject(err); else resolve({
                                name: filePath,
                                module: fileModuleName,
                                content: buffer
                            });
                        });
                    } catch (err) {
                        reject(err);
                    }
                }));

                continue;
            }

            let filePromise = new Promise(function (resolve, reject) {
                try {
                    fs.readFile(filePath, function (err, buffer) {
                        if (err) reject(err); else resolve({
                            name: filePath,
                            module: fileModuleName,
                            content: buffer
                        });
                    });
                } catch (err) {
                    reject(err);
                }
            });

            if (!config.build[fileExtension]) {
                throw new Error('No build config for extension "' + fileExtension + '".');
            }
            let steps = config.build[fileExtension][0];

            if (logger.isVerbose()) {
                filePromise = filePromise.then((file) => {
                    logger.log(
                        "Initializing file build steps \"" + filePath + "\".",
                        {action: "FILE_INIT", file: filePath, metadata: {}}
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

                            resolve(require(steps[j])(file));
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
                            fs.writeFile(cacheFile.dir + cacheFile.file, file.content, function (err) {
                                if (err) reject(err); else resolve(file);
                            });
                        } catch (err) {
                            reject(err);
                        }
                    });
                });
            }

            modulePromises.push(filePromise);
        }

        outputFilePromises.push(Promise.all(modulePromises).then(function (moduleFiles) {
            let ext = path.extname(outputFile);
            let content = '';

            for (let j = 0; j < moduleFiles.length; j++) {
                content += moduleFiles[j].content + "\n";
            }

            let steps = config.build[ext][1];
            let writers = config.build[ext][2];
            let moduleFile = {
                name: config.paths.root + outputFile,
                module: path.basename(outputFile, ext),
                content: content
            };

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

                moduleFile = require(steps[j])(moduleFile);
            }

            mkdirRecursive(config.paths.root, path.dirname(moduleFile.name));

            let writePromises = [];

            for (let j = 0; j < writers.length; j++) {
                if (logger.isVerbose()) {
                    logger.log(
                        "Writing \"" + moduleFile.name + "\" file with \"" + writers[j] + "\".",
                        {action: "WRITE_STEP", file: moduleFile.name, metadata: {step: writers[j]}}
                    );
                }

                writePromises.push(require(writers[j])(moduleFile));
            }

            // Collect all the write promises.
            let modulePromise = Promise.all(writePromises);

            // Make sure to log the write when all done.
            if (logger.isVerbose()) {
                modulePromise = modulePromise.then(function () {
                    logger.log(
                        "Done writing \"" + moduleFile.name + "\".",
                        {action: "WRITE", file: moduleFile.name, metadata: {}}
                    );
                });
            }

            return modulePromise;
        }));
    }

    return Promise.all(outputFilePromises);
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
            reject();
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
                isVerbose: () => {
                    return isVerbose;
                }
            };

        try {
            fs.accessSync(configFile, fs.constants.R_OK | fs.constants.W_OK);
            config = JSON.parse(fs.readFileSync(configFile));
        } catch (err) {
            stdout.error("Cannot read config file.");
            reject();
            return;
        }

        if (filesFile) {
            try {
                fs.accessSync(filesFile, fs.constants.R_OK | fs.constants.W_OK);
                files = JSON.parse(fs.readFileSync(filesFile));
            } catch (err) {
                stdout.error("Cannot read files file.");
                reject();
                return;
            }

            compile(config, files, logger)
                .then(() => resolve())
                .catch(() => reject());
        } else if (isStdIn) {
            let content = '';

            // we need to split it on newlines so unicode characters can not be split in 2 data events.
            let stream = stdin.pipe(require('split')());
            stream.on('data', (buf) => content += buf.toString() + '\n');
            stream.on('end', () => {
                compile(config, JSON.parse(content), logger)
                    .then(() => resolve())
                    .catch(() => reject());
            });
        } else {
            stdout.error("Cannot read stdin or files file.");
            reject();
        }
    });
}

exports.main = main;

// CLI entry point
if (require.main === module) {
    const args = process.argv.slice(2);
    main(args, process.stdin, console)
        .then(() => process.exitCode = 0)
        .catch(() => process.exitCode = 1);
}

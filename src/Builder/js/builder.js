let path = require('path'), fs = require('fs'), crypto = require('crypto');

let BuildableFile = function (filePath, extension, moduleName, needsRebuild, skipFileSteps, parentPath) {
    this.path = filePath;
    this.extension = extension !== undefined ? extension : path.extname(filePath);
    this.moduleName = moduleName !== undefined ? moduleName : filePath;
    this.needsRebuild = needsRebuild !== undefined ? needsRebuild : true;
    this.skipFileSteps = skipFileSteps !== undefined ? skipFileSteps : false;
    this.parentPath = parentPath !== undefined ? parentPath : path.dirname(filePath);
};

BuildableFile.fromData = function (data) {
    return new BuildableFile(data[0], data[1], data[2], data[3], data[4], data[5])
};

let File = function (name, module, content, outputFile, parentPath) {
    this.name = name;
    this.outputFile = outputFile;
    this.parentPath = parentPath;
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
    return new File(buildFile.path, buildFile.moduleName, content, buildFile.moduleName, buildFile.parentPath);
};

/**
 * @param { BuildableFile[] } files
 * @constructor
 */
let FileQueue = function (files) {
    this._files = files;

    this.length = function () {
        return this._files.length;
    };
    /**
     * @returns { BuildableFile[] }
     */
    this.all = function () {
        return this._files;
    };
    /**
     * @param { BuildableFile } file
     */
    this.add = function (file) {
        this._files.push(file);
    };
};

/**
 * Recursively create a directory given a directory, create the relative path.
 *
 * @param { string } rootDir
 * @param { string } pathToCreate
 */
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

let Builder = function () {
    /**
     * @param { BuildableFile } buildableFile
     * @param { FileQueue } fileQueue
     * @returns { Promise<File> }
     */
    this.compileFile = function (buildableFile, fileQueue) {
        return Promise.resolve(null);
    };

    /**
     * @param { string } outputFile
     * @param { File[] } moduleFiles
     * @returns { Promise<File> }
     */
    this.compileModule = function (outputFile, moduleFiles) {
        return Promise.resolve(null);
    };

    /**
     * @param { File } moduleFile
     * @returns {Promise<void>}
     */
    this.writeModule = function (moduleFile) {
        return Promise.resolve();
    };
};

/**
 * @param { object } config
 * @param { object } logger
 * @constructor
 */
let FileBuilder = function (config, logger) {
    /**
     * @param { BuildableFile } buildableFile
     * @param { FileQueue } fileQueue
     * @returns { Promise<File> }
     */
    this.compileFile = function (buildableFile, fileQueue) {
        if (config.build[buildableFile.extension] === undefined) {
            logger.error('No build config for extension "' + buildableFile.extension + '".');

            return Promise.reject('No build config for extension "' + buildableFile.extension + '".');
        }

        let file = File.fromBuildFile(buildableFile, fs.readFileSync(buildableFile.path));

        // Do we want to skip the file steps and go straight to the module?
        if (buildableFile.skipFileSteps) {
            if (logger.isVerbose()) {
                logger.log(
                    "Skipping build file steps for  \"" + buildableFile.path + "\".",
                    {action: "FILE_INIT", file: buildableFile.path, metadata: {}}
                );
            }

            return Promise.resolve(file);
        }

        if (logger.isVerbose()) {
            logger.log(
                "Initializing file build steps \"" + buildableFile.path + "\".",
                {action: "FILE_INIT", file: buildableFile.path, metadata: {}}
            );
        }

        let steps = config.build[buildableFile.extension][0];
        let filePromise = Promise.resolve(file);

        for (let j = 0; j < steps.length; j++) {
            filePromise = filePromise.then(function (file) {
                if (logger.isVerbose()) {
                    logger.log(
                        "Processing \"" + file.name + "\" file with \"" + steps[j] + "\".",
                        {action: "FILE_STEP", file: file.name, metadata: {step: steps[j]}}
                    );
                }

                return require(steps[j])(file, config, fileQueue);
            });
        }

        return filePromise;
    };

    /**
     * @param { string } outputFile
     * @param { File[] } moduleFiles
     * @returns { Promise<File> }
     */
    this.compileModule = function (outputFile, moduleFiles) {
        let ext = path.extname(outputFile);
        let content = [];

        for (let j = 0; j < moduleFiles.length; j++) {
            if (content.length > 0) {
                content.push(Buffer.from("\n"));
            }

            content.push(moduleFiles[j].content);
        }

        let steps = config.build[ext][1];
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

        let modulePromises = Promise.resolve(moduleFile);

        for (let j = 0; j < steps.length; j++) {
            modulePromises = modulePromises.then((moduleFile) => {
                if (logger.isVerbose()) {
                    logger.log(
                        "Processing \"" + moduleFile.name + "\" file with \"" + steps[j] + "\".",
                        {action: "MODULE_STEP", file: moduleFile.name, metadata: {step: steps[j]}}
                    );
                }

                return require(steps[j])(moduleFile, config);
            });
        }

        return modulePromises;
    };

    /**
     * @param { File } moduleFile
     * @returns {Promise<void>}
     */
    this.writeModule = function (moduleFile) {
        let ext = path.extname(moduleFile.name);
        let writers = config.build[ext][2];
        let writerPromises = [];

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
    };
};

FileBuilder.prototype = new Builder();

/**
 *
 * @param { Builder } inner
 * @param { object } config
 * @param { object } logger
 * @constructor
 */
let CachedBuilder = function (inner, config, logger) {
    function getCachedFileLocation(file) {
        let cleanPath = path.resolve(file);
        let rootDir = path.resolve(config.paths.root);
        let cacheDir = path.resolve(config.paths.cache);

        if (!cleanPath.startsWith(rootDir)) {
            throw new Error('Cannot get file "' + cleanPath + '" outside of the root (' + rootDir + ').');
        }
        let hash = crypto.createHash('sha1').update(cleanPath.substr(rootDir.length).replace(path.sep, '|')).digest('hex');

        return {
            dir: path.join(cacheDir, hash.substr(0, 2)),
            file: hash.substr(2)
        };
    }

    /**
     * @param { BuildableFile } buildableFile
     * @param { FileQueue } fileQueue
     * @returns { Promise<File> }
     */
    this.compileFile = function (buildableFile, fileQueue) {
        let cacheFile = getCachedFileLocation(buildableFile.path);
        let hasCache = fs.existsSync(path.join(cacheFile.dir, cacheFile.file));

        if (!buildableFile.needsRebuild && hasCache) {
            if (logger.isVerbose()) {
                logger.log(
                    "Reading from cache for \"" + buildableFile.path + "\".",
                    {action: "FILE_CACHE", file: buildableFile.path, metadata: {}}
                );
            }

            return Promise.resolve(File.fromBuildFile(
                buildableFile,
                fs.readFileSync(path.join(cacheFile.dir, cacheFile.file))
            ));
        }

        return inner.compileFile(buildableFile, fileQueue).then((file) => {
            mkdirRecursive(config.paths.root, cacheFile.dir);

            fs.writeFileSync(path.join(cacheFile.dir, cacheFile.file), file.content);

            return file;
        });
    };

    /**
     * @param { string } outputFile
     * @param { File[] } moduleFiles
     * @returns { Promise<File> }
     */
    this.compileModule = function (outputFile, moduleFiles) {
        return inner.compileModule(outputFile, moduleFiles);
    };

    /**
     * @param { File } moduleFile
     * @returns {Promise<void>}
     */
    this.writeModule = function (moduleFile) {
        return inner.writeModule(moduleFile);
    };
};

CachedBuilder.prototype = new Builder();

/**
 *
 * @param { Builder } builder
 * @param { BuildableFile[] } todo
 * @param { File[] } processed
 */
function buildAllFiles(builder, todo, processed) {
    let queue = new FileQueue([]);
    let promises = [];

    todo.forEach((file) => {
        promises.push(builder.compileFile(file, queue))
    });

    // Promise all already processed and the additional promises, files should contain all files.
    return Promise.all([...processed, ...promises]).then((files) => {
        // Are we done?
        if (queue.length() === 0) {
            return files;
        }

        // Process again with what is left in the queue.
        return buildAllFiles(builder, queue.all(), files);
    });
}

/**
 * Build everything
 *
 * @param { object } config
 * @param { object } files
 * @param { object } logger
 * @returns { Promise<void> }
 */
function build(config, files, logger) {
    /** @var { Builder } builder */
    let builder = new FileBuilder(config, logger);
    if (config.paths && config.paths.cache !== undefined) {
        builder = new CachedBuilder(builder, config, logger);
    }

    let outputFilePromises = [];

    for (let outputFile in files.input) {
        if (!files.input.hasOwnProperty(outputFile)) {
            continue;
        }

        let inputFiles = files.input[outputFile].map((file) => BuildableFile.fromData(file));
        let modulePromise = buildAllFiles(builder, inputFiles, []).then((moduleFiles) => {
            return builder.compileModule(outputFile, moduleFiles)
                .then((file) => builder.writeModule(file));
        });

        outputFilePromises.push(modulePromise);
    }

    return Promise.all(outputFilePromises).then(() => {});
}

module.exports.build = build;
module.exports.File = File;
module.exports.BuildableFile = BuildableFile;

let fs = require('fs'), builder = require('./builder');

function main(args, stdin, stdout) {
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
        return Promise.reject("Missing config file.");
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
        config = JSON.parse(fs.readFileSync(configFile).toString());
    } catch (err) {
        stdout.error("Cannot read config file.");
        return Promise.reject("Cannot read config file.");
    }

    config.const = {
        File : builder.File,
        BuildableFile : builder.BuildableFile
    };

    if (filesFile) {
        try {
            fs.accessSync(filesFile, fs.constants.R_OK | fs.constants.W_OK);
            files = JSON.parse(fs.readFileSync(filesFile).toString());
        } catch (err) {
            stdout.error("Cannot read files file.");
            return Promise.reject("Cannot read files file.");
        }

        return builder.build(config, files, logger);
    } else if (isStdIn) {
        let content = '';

        return new Promise((resolve, reject) => {
            // we need to split it on newlines so unicode characters can not be split in 2 data events.
            let stream = stdin.pipe(require('split')());
            stream.on('data', (buf) => content += buf.toString() + '\n');
            stream.on('end', () => {
                builder.build(config, JSON.parse(content), logger)
                    .then(() => resolve())
                    .catch((e) => reject(e));
            });
        });
    } else {
        stdout.error("Cannot read stdin or files file.");
        return Promise.reject("Cannot read stdin or files file.");
    }
}

exports.main = main;

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

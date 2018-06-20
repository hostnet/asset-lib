// Load promises jasmine plugin
// https://github.com/matthewjh/jasmine-promises/issues/3
global.jasmineRequire = global.jasmineRequire || require('jasmine-core');
require('jasmine-promises');

let path = require('path'), fs = require('fs');

describe("build.js", function () {
    let builder = require('../src/Builder/js/build');
    let remove = function(path) {
        if (fs.existsSync(path)) {
            if (fs.lstatSync(path).isDirectory()) {
                fs.readdirSync(path).forEach(function(file, index) {
                    remove(path + "/" + file);
                });
                fs.rmdirSync(path);
            } else {
                fs.unlinkSync(path);
            }
        }
    };

    it("when nothing given", function () {
        let mockLogger = {
            logs: [],
            log: function (msg) {
                this.logs.push(['LOG', msg]);
            },
            error: function (msg) {
                this.logs.push(['ERROR', msg]);
            }
        };

        return builder
            .main([], "", mockLogger)
            .then(() => fail())
            .catch(() => expect(mockLogger.logs[0]).toEqual(['ERROR', 'Missing config file.']));
    });

    it("with bad config", function () {
        let mockLogger = {
            logs: [],
            log: function (msg) {
                this.logs.push(['LOG', msg]);
            },
            error: function (msg) {
                this.logs.push(['ERROR', msg]);
            }
        };

        return builder
            .main(['idonotexists'], "", mockLogger)
            .then(() => fail())
            .catch(() => expect(mockLogger.logs[0]).toEqual(['ERROR', 'Cannot read config file.']));
    });

    it("with empty files", function () {
        let mockLogger = {
            logs: [],
            log: function (msg) {
                this.logs.push(['LOG', msg]);
            },
            error: function (msg) {
                this.logs.push(['ERROR', msg]);
            }
        };

        return builder
            .main([__dirname + '/fixtures/config.json'], "", mockLogger)
            .then(() => fail())
            .catch(() => expect(mockLogger.logs[0]).toEqual(['ERROR', 'Cannot read stdin or files file.']));
    });

    it("with bad config and files", function () {
        let mockLogger = {
            logs: [],
            log: function (msg) {
                this.logs.push(['LOG', msg]);
            },
            error: function (msg) {
                this.logs.push(['ERROR', msg]);
            }
        };

        return builder.main(
            ['--debug', '--verbose', '--log-json', __dirname + '/fixtures/config.json', 'idonotexisits'],
            "",
            mockLogger
        ).then(() => fail()).catch(() => expect(mockLogger.logs[0]).toEqual(['ERROR', 'Cannot read files file.']));
    });

    it("with empty config and files", function () {
        let mockLogger = {
            logs: [],
            log: function (msg) {
                this.logs.push(['LOG', msg]);
            },
            error: function (msg) {
                this.logs.push(['ERROR', msg]);
            }
        };

        return builder.main(
            [
                '--debug',
                '--verbose',
                '--log-json',
                __dirname + '/fixtures/config.json',
                __dirname + '/fixtures/files.json'
            ],
            "",
            mockLogger
        ).catch(() => fail());
    });

    it("build simple file", function () {
        let mockLogger = {
            logs: [],
            log: function (msg) {
                this.logs.push(['LOG', msg]);
            },
            error: function (msg) {
                this.logs.push(['ERROR', msg]);
            }
        };

        let config = {
            "mapping": {
                ".js": ".js"
            },
            "paths": {
                "root": path.join(__dirname, "fixtures"),
                "out": path.join(__dirname, "out"),
                "cache": path.join(__dirname, "fixtures", "var1")
            },
            "build": {
                ".js": [
                    [path.join(__dirname, '..', 'src', 'Builder', 'js', 'steps', 'identity.js')],
                    [path.join(__dirname, '..', 'src', 'Builder', 'js', 'steps', 'identity.js')],
                    [path.join(__dirname, '..', 'src', 'Builder', 'js', 'steps', 'identity.js')],
                ]
            }
        };
        let files = {
            "input": {
                'foo.js': [
                    [path.join(__dirname, 'fixtures', 'input', 'foo.js'), '.js', 'input.js', true, false]
                ]
            }
        };
        fs.writeFileSync(__dirname + "/fixtures/config1.simple.json", JSON.stringify(config));
        fs.writeFileSync(__dirname + "/fixtures/files1.simple.json", JSON.stringify(files));

        return builder.main(
            [
                '--debug',
                '--verbose',
                '--log-json',
                __dirname + '/fixtures/config1.simple.json',
                __dirname + '/fixtures/files1.simple.json'
            ],
            "",
            mockLogger
        )
            .then(() => {
                let inFile = path.join(__dirname, 'fixtures', 'input', 'foo.js');
                let outFile = path.join(__dirname, 'fixtures', 'foo.js');
                let step = path.join(__dirname, '..', 'src', 'Builder', 'js', 'steps', 'identity.js');

                expect(mockLogger.logs).toEqual([
                    [ 'LOG', JSON.stringify({action:"FILE_INIT", file: inFile, "metadata":{}}) ],
                    [ 'LOG', JSON.stringify({action:"FILE_STEP", file: inFile , "metadata":{step: step}}) ],
                    [ 'LOG', JSON.stringify({action:"MODULE_INIT", file: outFile , "metadata":{}}) ],
                    [ 'LOG', JSON.stringify({action:"MODULE_STEP", file: outFile , "metadata":{step: step}}) ],
                    [ 'LOG', JSON.stringify({action:"WRITE_STEP", file: outFile , "metadata":{step: step}}) ],
                    [ 'LOG', JSON.stringify({action:"WRITE", file: outFile , "metadata":{}}) ]
                ]);
            })
            .catch((e) => {console.error(e); fail(e)})
            .then(
                () => {
                    remove(__dirname + "\/fixtures\/var1");
                    remove(__dirname + "\/fixtures\/config1.simple.json");
                    remove(__dirname + "\/fixtures\/files1.simple.json");
                },
                () => {
                    remove(__dirname + "\/fixtures\/var1");
                    remove(__dirname + "\/fixtures\/config1.simple.json");
                    remove(__dirname + "\/fixtures\/files1.simple.json");
                }
            );
    });
    it("build simple file but skip file steps", function () {
        let mockLogger = {
            logs: [],
            log: function (msg) {
                this.logs.push(['LOG', msg]);
            },
            error: function (msg) {
                this.logs.push(['ERROR', msg]);
            }
        };

        let config = {
            "mapping": {
                ".js": ".js"
            },
            "paths": {
                "root": path.join(__dirname, "fixtures"),
                "out": path.join(__dirname, "out"),
                "cache": path.join(__dirname, "fixtures", "var2")
            },
            "build": {
                ".js": [
                    [path.join(__dirname, '..', 'src', 'Builder', 'js', 'steps', 'identity.js')],
                    [path.join(__dirname, '..', 'src', 'Builder', 'js', 'steps', 'identity.js')],
                    [path.join(__dirname, '..', 'src', 'Builder', 'js', 'steps', 'identity.js')],
                ]
            }
        };
        let files = {
            "input": {
                'foo.js': [
                    [path.join(__dirname, 'fixtures', 'input', 'foo.js'), '.js', 'input.js', true, true]
                ]
            }
        };
        fs.writeFileSync(__dirname + "/fixtures/config2.simple.json", JSON.stringify(config));
        fs.writeFileSync(__dirname + "/fixtures/files2.simple.json", JSON.stringify(files));

        return builder.main(
            [
                '--debug',
                '--verbose',
                '--log-json',
                __dirname + '/fixtures/config2.simple.json',
                __dirname + '/fixtures/files2.simple.json'
            ],
            "",
            mockLogger
        )
            .then(() => {
                let inFile = path.join(__dirname, 'fixtures', 'input', 'foo.js');
                let outFile = path.join(__dirname, 'fixtures', 'foo.js');
                let step = path.join(__dirname, '..', 'src', 'Builder', 'js', 'steps', 'identity.js');

                expect(mockLogger.logs).toEqual([
                    [ 'LOG', JSON.stringify({action:"FILE_INIT", file: inFile, "metadata":{}}) ],
                    [ 'LOG', JSON.stringify({action:"MODULE_INIT", file: outFile , "metadata":{}}) ],
                    [ 'LOG', JSON.stringify({action:"MODULE_STEP", file: outFile , "metadata":{step: step}}) ],
                    [ 'LOG', JSON.stringify({action:"WRITE_STEP", file: outFile , "metadata":{step: step}}) ],
                    [ 'LOG', JSON.stringify({action:"WRITE", file: outFile , "metadata":{}}) ]
                ]);
            })
            .catch((e) => fail(e))
            .then(
                () => {
                    remove(__dirname + "\/fixtures\/var2");
                    remove(__dirname + "\/fixtures\/config2.simple.json");
                    remove(__dirname + "\/fixtures\/files2.simple.json");
                },
                () => {
                    remove(__dirname + "\/fixtures\/var2");
                    remove(__dirname + "\/fixtures\/config2.simple.json");
                    remove(__dirname + "\/fixtures\/files2.simple.json");
                }
            );
    });

    it("build simple file with up-to-date file but no cache", function () {
        let mockLogger = {
            logs: [],
            log: function (msg) {
                this.logs.push(['LOG', msg]);
            },
            error: function (msg) {
                this.logs.push(['ERROR', msg]);
            }
        };

        let config = {
            "mapping": {
                ".js": ".js"
            },
            "paths": {
                "root": path.join(__dirname, "fixtures"),
                "out": path.join(__dirname, "out"),
                "cache": path.join(__dirname, "fixtures", "var3")
            },
            "build": {
                ".js": [
                    [path.join(__dirname, '..', 'src', 'Builder', 'js', 'steps', 'identity.js')],
                    [path.join(__dirname, '..', 'src', 'Builder', 'js', 'steps', 'identity.js')],
                    [path.join(__dirname, '..', 'src', 'Builder', 'js', 'steps', 'identity.js')],
                ]
            }
        };
        let files = {
            "input": {
                'foo.js': [
                    [path.join(__dirname, 'fixtures', 'input', 'foo.js'), '.js', 'input.js', false, false]
                ]
            }
        };
        fs.writeFileSync(__dirname + "/fixtures/config3.simple.json", JSON.stringify(config));
        fs.writeFileSync(__dirname + "/fixtures/files3.simple.json", JSON.stringify(files));

        return builder.main(
            [
                '--debug',
                '--verbose',
                '--log-json',
                __dirname + '\/fixtures\/config3.simple.json',
                __dirname + '\/fixtures\/files3.simple.json'
            ],
            "",
            mockLogger
        )
            .then(() => {
                let inFile = path.join(__dirname, 'fixtures', 'input', 'foo.js');
                let outFile = path.join(__dirname, 'fixtures', 'foo.js');
                let step = path.join(__dirname, '..', 'src', 'Builder', 'js', 'steps', 'identity.js');

                expect(mockLogger.logs).toEqual([
                    [ 'LOG', JSON.stringify({action:"FILE_INIT", file: inFile, "metadata":{}}) ],
                    [ 'LOG', JSON.stringify({action:"FILE_STEP", file: inFile , "metadata":{step: step}}) ],
                    [ 'LOG', JSON.stringify({action:"MODULE_INIT", file: outFile , "metadata":{}}) ],
                    [ 'LOG', JSON.stringify({action:"MODULE_STEP", file: outFile , "metadata":{step: step}}) ],
                    [ 'LOG', JSON.stringify({action:"WRITE_STEP", file: outFile , "metadata":{step: step}}) ],
                    [ 'LOG', JSON.stringify({action:"WRITE", file: outFile , "metadata":{}}) ]
                ]);
            })
            .catch((e) => fail(e))
            .then(
                () => {
                    remove(__dirname + "\/fixtures\/var3");
                    remove(__dirname + "\/fixtures\/config3.simple.json");
                    remove(__dirname + "\/fixtures\/files3.simple.json");
                },
                () => {
                    remove(__dirname + "\/fixtures\/var3");
                    remove(__dirname + "\/fixtures\/config3.simple.json");
                    remove(__dirname + "\/fixtures\/files3.simple.json");
                }
            );
    });

    it("build simple file with up-to-date file with cache", function () {
        let mockLogger = {
            logs: [],
            log: function (msg) {
                this.logs.push(['LOG', msg]);
            },
            error: function (msg) {
                this.logs.push(['ERROR', msg]);
            }
        };

        let config = {
            "mapping": {
                ".js": ".js"
            },
            "paths": {
                "root": path.join(__dirname, "fixtures"),
                "out": path.join(__dirname, "out"),
                "cache": path.join(__dirname, "fixtures", "var4")
            },
            "build": {
                ".js": [
                    [path.join(__dirname, '..', 'src', 'Builder', 'js', 'steps', 'identity.js')],
                    [path.join(__dirname, '..', 'src', 'Builder', 'js', 'steps', 'identity.js')],
                    [path.join(__dirname, '..', 'src', 'Builder', 'js', 'steps', 'identity.js')],
                ]
            }
        };
        let files = {
            "input": {
                'foo.js': [
                    [path.join(__dirname, 'fixtures', 'input', 'foo.js'), '.js', 'input.js', false, false]
                ]
            }
        };
        fs.writeFileSync(__dirname + "/fixtures/config4.simple.json", JSON.stringify(config));
        fs.writeFileSync(__dirname + "/fixtures/files4.simple.json", JSON.stringify(files));

        // First perform a build. Then do it again and check that result
        return builder.main(
            [
                '--debug',
                '--verbose',
                '--log-json',
                __dirname + '/fixtures/config4.simple.json',
                __dirname + '/fixtures/files4.simple.json'
            ],
            "",
            mockLogger
        )
            .then(() => {
                // reset the logger
                mockLogger.logs = [];

                // Do another build.
                return builder.main(
                    [
                        '--debug',
                        '--verbose',
                        '--log-json',
                        __dirname + '/fixtures/config4.simple.json',
                        __dirname + '/fixtures/files4.simple.json'
                    ],
                    "",
                    mockLogger
                )
            })
            .then(() => {
                let inFile = path.join(__dirname, 'fixtures', 'input', 'foo.js');
                let outFile = path.join(__dirname, 'fixtures', 'foo.js');
                let step = path.join(__dirname, '..', 'src', 'Builder', 'js', 'steps', 'identity.js');

                expect(mockLogger.logs).toEqual([
                    [ 'LOG', JSON.stringify({action:"FILE_CACHE", file: inFile, "metadata":{}}) ],
                    [ 'LOG', JSON.stringify({action:"MODULE_INIT", file: outFile , "metadata":{}}) ],
                    [ 'LOG', JSON.stringify({action:"MODULE_STEP", file: outFile , "metadata":{step: step}}) ],
                    [ 'LOG', JSON.stringify({action:"WRITE_STEP", file: outFile , "metadata":{step: step}}) ],
                    [ 'LOG', JSON.stringify({action:"WRITE", file: outFile , "metadata":{}}) ]
                ]);
            })
            .catch((e) => fail(e))
            .then(
                () => {
                    remove(__dirname + "\/fixtures\/var4");
                    remove(__dirname + "\/fixtures\/config4.simple.json");
                    remove(__dirname + "\/fixtures\/files4.simple.json");
                },
                () => {
                    remove(__dirname + "\/fixtures\/var4");
                    remove(__dirname + "\/fixtures\/config4.simple.json");
                    remove(__dirname + "\/fixtures\/files4.simple.json");
                }
            );
    });


    it("build simple file quiet", function () {
        let mockLogger = {
            logs: [],
            log: function (msg) {
                this.logs.push(['LOG', msg]);
            },
            error: function (msg) {
                this.logs.push(['ERROR', msg]);
            }
        };

        let config = {
            "mapping": {
                ".js": ".js"
            },
            "paths": {
                "root": path.join(__dirname, "fixtures"),
                "out": path.join(__dirname, "out")
            },
            "build": {
                ".js": [
                    [path.join(__dirname, '..', 'src', 'Builder', 'js', 'steps', 'identity.js')],
                    [path.join(__dirname, '..', 'src', 'Builder', 'js', 'steps', 'identity.js')],
                    [path.join(__dirname, '..', 'src', 'Builder', 'js', 'steps', 'identity.js')],
                ]
            }
        };
        let files = {
            "input": {
                'foo.js': [
                    [path.join(__dirname, 'fixtures', 'input', 'foo.js'), '.js', 'input.js', true, false]
                ]
            }
        };
        fs.writeFileSync(__dirname + "/fixtures/config5.simple.json", JSON.stringify(config));
        fs.writeFileSync(__dirname + "/fixtures/files5.simple.json", JSON.stringify(files));

        return builder.main(
            [__dirname + '/fixtures/config5.simple.json', __dirname + '/fixtures/files5.simple.json'],
            "",
            mockLogger
        )
            .then(() => {
                expect(mockLogger.logs).toEqual([]);
            })
            .catch(() => fail())
            .then(
                () => {
                    remove(__dirname + "\/fixtures\/config5.simple.json");
                    remove(__dirname + "\/fixtures\/files5.simple.json");
                },
                () => {
                    remove(__dirname + "\/fixtures\/config5.simple.json");
                    remove(__dirname + "\/fixtures\/files5.simple.json");
                }
            );
    });
});

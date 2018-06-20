let fs = require('fs');

describe("build.js", function () {
    let builder = require('../src/Builder/js/build');
    let mockLogger = {
        logs: [],
        log: function (msg) {
            this.logs.push(['LOG', msg]);
        },
        error: function (msg) {
            this.logs.push(['ERROR', msg]);
        }
    };
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

    beforeEach(function () {
        // reset logger
        mockLogger.logs = [];
    });

    it("when nothing given", function (done) {
        return builder
            .main([], "", mockLogger)
            .then(() => {
                fail();
                done()
            })
            .catch(() => {
                expect(mockLogger.logs[0]).toEqual(['ERROR', 'Missing config file.']);
                done();
            });
    });

    it("with bad config", function (done) {
        return builder
            .main(['idonotexists'], "", mockLogger)
            .then(() => {
                fail();
                done()
            })
            .catch(() => {
                expect(mockLogger.logs[0]).toEqual(['ERROR', 'Cannot read config file.']);
                done();
            });
    });

    it("with empty files", function (done) {
        return builder
            .main([__dirname + '/fixtures/config.json'], "", mockLogger)
            .then(() => {
                fail();
                done()
            })
            .catch(() => {
                expect(mockLogger.logs[0]).toEqual(['ERROR', 'Cannot read stdin or files file.']);
                done();
            });
    });

    it("with bad config and files", function (done) {
        return builder.main(
            ['--debug', '--verbose', '--log-json', __dirname + '/fixtures/config.json', 'idonotexisits'],
            "",
            mockLogger
        ).then(() => {fail(); done()}).catch(() => {
            expect(mockLogger.logs[0]).toEqual(['ERROR', 'Cannot read files file.']);
            done();
        });
    });

    it("with empty config and files", function (done) {
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
        )
            .catch(() => fail())
            .finally(() => done());
    });

    it("build simple file", function (done) {
        let config = {
            "mapping": {
                ".js": ".js"
            },
            "paths": {
                "root": __dirname + "\/fixtures\/",
                "out": __dirname + "\/out\/",
                "cache": __dirname + "\/fixtures\/var1\/"
            },
            "build": {
                ".js": [
                    [__dirname + '/../src/Builder/js/steps/identity.js'],
                    [__dirname + '/../src/Builder/js/steps/identity.js'],
                    [__dirname + '/../src/Builder/js/steps/identity.js'],
                ]
            }
        };
        let files = {
            "input": {
                'foo.js': [
                    [__dirname + '/fixtures/input/foo.js', '.js', 'input.js', true, false]
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
                let inFile = '"file":"' + __dirname + '/fixtures/input/foo.js"';
                let outFile = '"file":"' + __dirname + '/fixtures/foo.js"';
                let step = '"step":"' + __dirname + '/../src/Builder/js/steps/identity.js"';

                expect(mockLogger.logs).toEqual([
                    [ 'LOG', '{"action":"FILE_INIT",' + inFile+ ',"metadata":{}}' ],
                    [ 'LOG', '{"action":"FILE_STEP",' + inFile + ',"metadata":{' + step + '}}' ],
                    [ 'LOG', '{"action":"MODULE_INIT",' + outFile + ',"metadata":{}}' ],
                    [ 'LOG', '{"action":"MODULE_STEP",' + outFile + ',"metadata":{' + step + '}}' ],
                    [ 'LOG', '{"action":"WRITE_STEP",' + outFile + ',"metadata":{' + step + '}}' ],
                    [ 'LOG', '{"action":"WRITE",' + outFile + ',"metadata":{}}' ]
                ]);
            })
            .catch(() => fail())
            .finally(() => remove(__dirname + "\/fixtures\/var1"))
            .finally(() => remove(__dirname + "\/fixtures\/config1.simple.json"))
            .finally(() => remove(__dirname + "\/fixtures\/files1.simple.json"))
            .finally(() => done());
    });
    it("build simple file but skip file steps", function (done) {
        let config = {
            "mapping": {
                ".js": ".js"
            },
            "paths": {
                "root": __dirname + "\/fixtures\/",
                "out": __dirname + "\/out\/",
                "cache": __dirname + "\/fixtures\/var2\/"
            },
            "build": {
                ".js": [
                    [__dirname + '/../src/Builder/js/steps/identity.js'],
                    [__dirname + '/../src/Builder/js/steps/identity.js'],
                    [__dirname + '/../src/Builder/js/steps/identity.js']
                ]
            }
        };
        let files = {
            "input": {
                'foo.js': [
                    [__dirname + '/fixtures/input/foo.js', '.js', 'input.js', true, true]
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
                let inFile = '"file":"' + __dirname + '/fixtures/input/foo.js"';
                let outFile = '"file":"' + __dirname + '/fixtures/foo.js"';
                let step = '"step":"' + __dirname + '/../src/Builder/js/steps/identity.js"';

                expect(mockLogger.logs).toEqual([
                    [ 'LOG', '{"action":"FILE_INIT",' + inFile+ ',"metadata":{}}' ],
                    [ 'LOG', '{"action":"MODULE_INIT",' + outFile + ',"metadata":{}}' ],
                    [ 'LOG', '{"action":"MODULE_STEP",' + outFile + ',"metadata":{' + step + '}}' ],
                    [ 'LOG', '{"action":"WRITE_STEP",' + outFile + ',"metadata":{' + step + '}}' ],
                    [ 'LOG', '{"action":"WRITE",' + outFile + ',"metadata":{}}' ]
                ]);
            })
            .catch((e) => fail(e))
            .finally(() => remove(__dirname + "\/fixtures\/var2"))
            .finally(() => remove(__dirname + "\/fixtures\/config2.simple.json"))
            .finally(() => remove(__dirname + "\/fixtures\/files2.simple.json"))
            .finally(() => done());
    });

    it("build simple file with up-to-date file but no cache", function (done) {
        let config = {
            "mapping": {
                ".js": ".js"
            },
            "paths": {
                "root": __dirname + "\/fixtures\/",
                "out": __dirname + "\/out\/",
                "cache": __dirname + "\/fixtures\/var3\/"
            },
            "build": {
                ".js": [
                    [__dirname + '/../src/Builder/js/steps/identity.js'],
                    [__dirname + '/../src/Builder/js/steps/identity.js'],
                    [__dirname + '/../src/Builder/js/steps/identity.js']
                ]
            }
        };
        let files = {
            "input": {
                'foo.js': [
                    [__dirname + '/fixtures/input/foo.js', '.js', 'input.js', false, false]
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
                let inFile = '"file":"' + __dirname + '/fixtures/input/foo.js"';
                let outFile = '"file":"' + __dirname + '/fixtures/foo.js"';
                let step = '"step":"' + __dirname + '/../src/Builder/js/steps/identity.js"';

                expect(mockLogger.logs).toEqual([
                    [ 'LOG', '{"action":"FILE_INIT",' + inFile+ ',"metadata":{}}' ],
                    [ 'LOG', '{"action":"FILE_STEP",' + inFile + ',"metadata":{' + step + '}}' ],
                    [ 'LOG', '{"action":"MODULE_INIT",' + outFile + ',"metadata":{}}' ],
                    [ 'LOG', '{"action":"MODULE_STEP",' + outFile + ',"metadata":{' + step + '}}' ],
                    [ 'LOG', '{"action":"WRITE_STEP",' + outFile + ',"metadata":{' + step + '}}' ],
                    [ 'LOG', '{"action":"WRITE",' + outFile + ',"metadata":{}}' ]
                ]);
            })
            .catch((e) => fail(e))
            .finally(() => remove(__dirname + "\/fixtures\/var3"))
            .finally(() => remove(__dirname + "\/fixtures\/config3.simple.json"))
            .finally(() => remove(__dirname + "\/fixtures\/files3.simple.json"))
            .finally(() => done());
    });

    it("build simple file with up-to-date file with cache", function (done) {
        let config = {
            "mapping": {
                ".js": ".js"
            },
            "paths": {
                "root": __dirname + "\/fixtures\/",
                "out": __dirname + "\/out\/",
                "cache": __dirname + "\/fixtures\/var4\/"
            },
            "build": {
                ".js": [
                    [__dirname + '/../src/Builder/js/steps/identity.js'],
                    [__dirname + '/../src/Builder/js/steps/identity.js'],
                    [__dirname + '/../src/Builder/js/steps/identity.js']
                ]
            }
        };
        let files = {
            "input": {
                'foo.js': [
                    [__dirname + '/fixtures/input/foo.js', '.js', 'input.js', false, false]
                ]
            }
        };
        fs.writeFileSync(__dirname + "/fixtures/config4.simple.json", JSON.stringify(config));
        fs.writeFileSync(__dirname + "/fixtures/files4.simple.json", JSON.stringify(files));

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
                let inFile = '"file":"' + __dirname + '/fixtures/input/foo.js"';
                let outFile = '"file":"' + __dirname + '/fixtures/foo.js"';
                let step = '"step":"' + __dirname + '/../src/Builder/js/steps/identity.js"';

                expect(mockLogger.logs).toEqual([
                    [ 'LOG', '{"action":"FILE_CACHE",' + inFile+ ',"metadata":{}}' ],
                    [ 'LOG', '{"action":"MODULE_INIT",' + outFile + ',"metadata":{}}' ],
                    [ 'LOG', '{"action":"MODULE_STEP",' + outFile + ',"metadata":{' + step + '}}' ],
                    [ 'LOG', '{"action":"WRITE_STEP",' + outFile + ',"metadata":{' + step + '}}' ],
                    [ 'LOG', '{"action":"WRITE",' + outFile + ',"metadata":{}}' ]
                ]);
            })
            .catch((e) => fail(e))
            .finally(() => remove(__dirname + "\/fixtures\/config4.simple.json"))
            .finally(() => remove(__dirname + "\/fixtures\/files4.simple.json"))
            .finally(() => done());
    });


    it("build simple file quiet", function (done) {
        let config = {
            "mapping": {
                ".js": ".js"
            },
            "paths": {
                "root": __dirname + "\/fixtures\/",
                "out": __dirname + "\/out\/"
            },
            "build": {
                ".js": [
                    [__dirname + '/../src/Builder/js/steps/identity.js'],
                    [__dirname + '/../src/Builder/js/steps/identity.js'],
                    [__dirname + '/../src/Builder/js/steps/identity.js'],
                ]
            }
        };
        let files = {
            "input": {
                'foo.js': [
                    [__dirname + '/fixtures/input/foo.js', '.js', 'input.js', true, false]
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
            .finally(() => remove(__dirname + "\/fixtures\/config5.simple.json"))
            .finally(() => remove(__dirname + "\/fixtures\/files5.simple.json"))
            .finally(() => done());
    });
});

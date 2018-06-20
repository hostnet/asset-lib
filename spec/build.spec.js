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

    it("when nothing given", function (done) {
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
        ).then(() => {fail(); done()}).catch(() => {
            expect(mockLogger.logs[0]).toEqual(['ERROR', 'Cannot read files file.']);
            done();
        });
    });

    it("with empty config and files", function (done) {
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
        )
            .catch(() => fail())
            .then(() => done(), () => done());
    });

    it("build simple file", function (done) {
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
                    done();
                },
                () => {
                    remove(__dirname + "\/fixtures\/var1");
                    remove(__dirname + "\/fixtures\/config1.simple.json");
                    remove(__dirname + "\/fixtures\/files1.simple.json");
                    done();
                }
            );
    });
    it("build simple file but skip file steps", function (done) {
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
                    done();
                },
                () => {
                    remove(__dirname + "\/fixtures\/var2");
                    remove(__dirname + "\/fixtures\/config2.simple.json");
                    remove(__dirname + "\/fixtures\/files2.simple.json");
                    done();
                }
            );
    });

    it("build simple file with up-to-date file but no cache", function (done) {
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
                    done();
                },
                () => {
                    remove(__dirname + "\/fixtures\/var3");
                    remove(__dirname + "\/fixtures\/config3.simple.json");
                    remove(__dirname + "\/fixtures\/files3.simple.json");
                    done();
                }
            );
    });

    it("build simple file with up-to-date file with cache", function (done) {
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
                    remove(__dirname + "\/fixtures\/config4.simple.json");
                    remove(__dirname + "\/fixtures\/files4.simple.json");
                    done();
                },
                () => {
                    remove(__dirname + "\/fixtures\/config4.simple.json");
                    remove(__dirname + "\/fixtures\/files4.simple.json");
                    done();
                }
            );
    });


    it("build simple file quiet", function (done) {
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
                    done();
                },
                () => {
                    remove(__dirname + "\/fixtures\/config5.simple.json");
                    remove(__dirname + "\/fixtures\/files5.simple.json");
                    done();
                }
            );
    });
});

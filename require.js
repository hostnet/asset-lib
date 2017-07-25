(function(window) {
    var _modules = {};

    /**
     * Return a load promise. After the promise is fulfilled, the module is
     * loaded.
     *
     * @param {string} module
     * @returns {Promise}
     * @private
     */
    var _xhr = function (module) {
        return new Promise(function (resolve, reject) {
            var url = '/' + module + '.js';
            var xhr = new XMLHttpRequest();

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (200 !== xhr.status) {
                        reject('Cannot load module "' + module + '" ("' + url + '").');
                    } else {
                        resolve({
                            module : module,
                            address : url,
                            source: xhr.response || xhr.responseText
                        });
                    }
                }
            };

            xhr.open("GET", url, true);
            xhr.send(null);
        });
    };

    /**
     * Return a register promise. After the promise is fulfilled, the module is
     * registered as a definition.
     *
     * @param {object} data
     * @returns {Promise}
     * @private
     */
    var _register = function (data) {
        var src = window.require.translator(data);

        var init = eval.apply(window, ['(function (define) {\n' + src.source + '\n})']);
        var _define = function (a, b, c) {
            if (typeof a === 'string') {
                if (a !== this.allowed_module) {
                    throw new Error('Module "' + this.allowed_module + '" expected, got "' + a + '".');
                }
                return _defineResolve(a, b, c, data.address);
            }
            return _defineResolve(this.allowed_module, a, b, data.address);
        }.bind({allowed_module: data.module});

        init(_define);

        return _modules[data.module].loader = new Promise(function (resolve) {
            resolve(_modules[data.module]);
        });
    };

    /**
     * Return a load promise. After the promise is fulfilled, the module is
     * loaded and defined.
     *
     * @param {string} name
     * @returns {Promise}
     * @private
     */
    var _load = function (name) {
        // is it pre-defined?
        if (_modules[name]) {
            // do we already have a loader?
            if (null !== _modules[name].loader) {
                return _modules[name].loader;
            }

            return _modules[name].loader = new Promise(function (resolve) {
                resolve(_modules[name]);
            });
        }
        // try to load it
        return _xhr(name).then(_register);
    };

    /**
     * Return a resolve promise. After the promise is fulfilled, all the
     * module's dependencies are initialized and ready to be required.
     *
     * @param {object} metadata
     * @returns {Promise}
     * @private
     */
    var _resolve = function (metadata) {
        var promises = [];

        for (var i = 0; i < metadata.dependencies.length; i++) {
            var dep = metadata.dependencies[i];

            if (dep === 'require') {
                promises.push(Promise.resolve(function (name) {
                    return _modules[name].module.exports;
                }));
            } else if (dep === 'exports') {
                promises.push(_modules[metadata.name].module.exports);
            } else if (dep === 'module') {
                promises.push(_modules[metadata.name].module);
            } else {
                promises.push(_require(dep));
            }
        }

        return Promise.all(promises).then(function(args) {
            return new Promise(function (resolve) {
                resolve({
                    name: metadata.name,
                    initializer: metadata.initializer,
                    args : args
                });
            })
        });
    };

    /**
     * Return a initialization promise. After the promise is fulfilled, the
     * module is initialized.
     *
     * @param {object} module
     * @returns {Promise}
     * @private
     */
    var _initialize = function (module) {
        module.initializer.apply(null, module.args);
    };

    /**
     * Return a require promise. After the promise is fulfilled, the module has
     * been required.
     *
     * This goes through various promises but the flow is always the same:
     *  -> LOAD -> RESOLVE -> INITIALIZE
     *
     *  During the LOAD phase the module is fetched, if already defined this
     *  will do very little.
     *  During the RESOLVE phase dependencies are initialized. This will create
     *  a tree of promises which need to be fulfilled in order to resolve the
     *  require.
     *  During the INITIALIZE phase the module function is executed and this
     *  will populate the module.exports property.
     *
     * @param {string} name
     * @returns {Promise}
     * @private
     */
    var _require = function (name) {
        return _load(name)
            .then(_resolve)
            .then(_initialize);
    };

    /**
     * Polymorphic define decorator. Supports multiple signatures.
     *
     * @param {string|object|function} a
     * @param {object|function|undefined} b
     * @param {function|undefined} c
     * @param {string|undefined} definer
     * @private
     */
    var _defineResolve = function (a, b, c, definer) {
        if (typeof a === 'string' && typeof b === 'object' && typeof c === 'function') {
            return _define(a, b, c, definer);
        }
        if (typeof a === 'string' && typeof b === 'function' && typeof c === 'undefined') {
            return _define(a, [], b, definer);
        }
        if (typeof a === 'object' && typeof b === 'function' && typeof c === 'undefined') {
            return _define('main', a, b, definer);
        }
        if (typeof a === 'function' && typeof b === 'undefined' && typeof c === 'undefined') {
            return _define('main', [], a, definer);
        }

        throw new Error(
            'Unknown combination of arguments, expected (string|object|function[, object|function[, function]]); got: (' + typeof a + ', ' + typeof b + ', ' + typeof c + ')'
        );
    };

    /**
     * Define a module. This registers the module internally so it can be
     * required.
     *
     * @param {string} name
     * @param {object} dependencies
     * @param {function} initializer
     * @param {string|undefined} definer
     * @private
     */
    var _define = function (name, dependencies, initializer, definer) {
        if (name.length === 0) {
            throw new Error('Cannot declare module with an empty name.');
        }
        if (_modules[name]) {
            throw new Error('Cannot redeclare module "' + name + '". Already declared ' + _modules[name].definer);
        }

        // Get the definer
        if (typeof definer === 'undefined') {
            definer = 'at unknown';
            try {
                throw Error('')
            } catch (e) {
                var r = /([^@\s\(\)]*?:[0-9]+(:[0-9]+)?)/gm;

                r.exec(e.stack); // skip one: this._define
                r.exec(e.stack); // skip one: this._defineResolve
                r.exec(e.stack); // skip one: window.define
                m = r.exec(e.stack); // module call

                definer = m[1];
            }
        }

        _modules[name] = {
            name : name,
            initializer : initializer,
            dependencies: dependencies,
            definer: definer,
            loader : null,
            module : {exports: {}}
        };
    };

    /**
     * Require a module. Note, this will load the module Async.
     *
     * @param {string} name
     */
    window.require = function (name) {
        _require(name);
    };

    /**
     * Translate data loaded using XHR. This allows for quick changes to the
     * required source.
     *
     * @param {object} load
     * @returns {object}
     */
    window.require.translator = function (load) {
        return load;
    };

    /**
     * AMD compatible define method. Allows for defining modules.
     *
     * Supported signatures are:
     *  - define("name", ["dep1", "dep2"], function (dep_1, dep_2) {});
     *  - define(["dep1", "dep2"], function (dep_1, dep_2) {});
     *  - define("name", function () {});
     *  - define(function () {});
     *
     * @param {string|object|function} a
     * @param {object|function|undefined} b
     * @param {function|undefined} c
     */
    window.define = function (a, b, c) {
        return _defineResolve(a, b, c, undefined);
    };
})(window);

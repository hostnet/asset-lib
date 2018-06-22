(function (window) {
    if (typeof window.require === 'function') {
        return;
    }

    var _modules = {};

    // Exceptions
    var RequireError = function (name) {
        this.message = 'Cannot find module "' + name + '", did you define it?';
    };
    var ModuleRedeclareError = function (name) {
        this.message = 'Cannot redeclare module "' + name + '".';
    };

    RequireError.prototype = new Error();
    ModuleRedeclareError.prototype = new Error();

    var _resolveRelativeName = function (name, parent_module) {
        var require_name = name;

        if (require_name.indexOf('.') === 0) {
            var parent = parent_module.substring(0, parent_module.lastIndexOf('/'));

            if (require_name.indexOf('./') === 0) {
                require_name = require_name.substr(2);

                if (parent.indexOf('/') === -1) {
                    // If the parent is empty, use the full require name without the ./
                    if (parent.length === 0) {
                        return require_name;
                    }
                    // else append the parent
                    return parent + '/' + require_name;
                }
            } else {
                while (require_name.indexOf('../') === 0) {
                    if (parent.indexOf('/') === -1) {
                        return require_name.substr(3);
                    }

                    require_name = require_name.substr(3);
                    parent = parent.substring(0, parent.lastIndexOf('/'))
                }
            }

            return parent + '/' + require_name;
        }

        return require_name;
    };

    var _initialize = function (name) {
        var _require = function (import_name) {
            return window.require(_resolveRelativeName(import_name, name));
        };

        var _module = {exports: {}};
        var _define = function (a, b, c) {
            var module_name = name, initializer, dependencies;
            if (!c) {
                if (!b) {
                    dependencies = [];
                    initializer = a;
                } else if (typeof a === 'string' && typeof b === 'function') {
                    // define("foo", function (require, exports, module) {})
                    module_name = a;
                    dependencies = ['require', 'exports', 'module'];
                    initializer = b;
                } else {
                    // define(["dep1", "dep2"], function (dep_1, dep_2) {})

                    dependencies = a;
                    initializer = typeof b === 'function' ? b : function () { return b; };
                }
            } else {
                // define("foo", ["dep1", "dep2"], function (dep_1, dep_2) {})

                module_name = a;
                dependencies = b;
                initializer = c;
            }

            var args = [];

            for (var i = 0, n = dependencies.length; i < n; i++) {
                var dependency_name = dependencies[i];
                var dependency;

                if (dependency_name === 'require') {
                    dependency = _require;
                } else if (dependency_name === 'module') {
                    dependency = _module;
                } else if (dependency_name === 'exports') {
                    dependency = _module.exports;
                } else {
                    dependency = window.require(_resolveRelativeName(dependency_name, module_name));
                }

                args.push(dependency);
            }

            _module.exports = initializer.apply(null, args) || _module.exports;

            if (!_modules[module_name]) {
                _modules[module_name] = {};
            }
            _modules[module_name]._module = _module.exports;
        };
        _define.amd = {};

        var result = _modules[name]._initializer.apply(null, [_define, _require, _module, _module.exports]);
        _modules[name]._module = result || _module.exports;
    };

    window.require = function (name) {
        if (!_modules[name]) {
            throw new RequireError(name);
        }

        if (null === _modules[name]._module) {
            _initialize(name);
        }

        return _modules[name]._module;
    };

    window.register = function (name, initializer) {
        if (_modules[name]) {
            if (typeof console !== 'undefined' && typeof console.warn === 'function') {
                console.warn(new ModuleRedeclareError(name));
            }
            return;
        }

        _modules[name] = {
            _initializer: initializer,
            _module: null
        };
    };
})(typeof window !== "undefined" ? window : this);

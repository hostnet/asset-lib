describe("Require.js module register method test", function () {
    var lib;

    beforeEach(function () {
        lib = require('../src/Builder/js/require');
        spyOn(console, 'warn');
    });

    it("with jquery and a plugin", function () {
        lib.register("jquery", function (define, require, module, exports) {
            // jquery
            define('jquery', [], function () {
                return {'fn' : {}};
            });
        });
        lib.register("jplug", function (define, require, module, exports) {
            (function (factory) {
                if (typeof define === 'function' && define.amd) {
                    // AMD. Register as an anonymous module.
                    define(['jquery'], factory);
                } else if (typeof module === 'object' && module.exports) {
                    // Node/CommonJS
                    module.exports = function( root, jQuery ) {
                        if ( jQuery === undefined ) {
                            // require('jQuery') returns a factory that requires window to
                            // build a jQuery instance, we normalize how we use modules
                            // that require this pattern but the window provided is a noop
                            // if it's defined (how jquery works)
                            if ( typeof window !== 'undefined' ) {
                                jQuery = require('jquery');
                            }
                            else {
                                jQuery = require('jquery')(root);
                            }
                        }
                        factory(jQuery);
                        return jQuery;
                    };
                } else {
                    // Browser globals
                    factory(jQuery);
                }
            }(function ($) {
                $.fn.jqueryPlugin = function () { return true; };
            }));
        });

        expect(lib.require('jquery')).toEqual({'fn' : {}});
        expect(lib.require('jquery').fn.jqueryPlugin).toBeUndefined();

        lib.require('jplug');

        expect(lib.require('jquery').fn.jqueryPlugin).toBeDefined();
    });

    it("with AMD define", function() {
        lib.register("foo", function (define, require, module, exports) {
            define([], function () {
                return 'FOO';
            });
        });

        expect(lib.require('foo')).toEqual('FOO');
    });

    it("with AMD define object", function() {
        lib.register("foo-static", function (define, require, module, exports) {
            define([], {foo: 1});
        });

        expect(lib.require('foo-static')).toEqual({foo: 1});
    });

    it("with AMD define and reserved requirements", function() {
        lib.register("amd_define/foo", function (define, require, module, exports) {
            define([], function () {
                return 'FOO';
            });
        });

        lib.register("amd_define/baz", function (define, require, module, exports) {
            define(['amd_define/foo', 'require', 'module', 'exports'], function ($, require, module, exports) {
                return 'BAZ';
            });
        });

        expect(lib.require('amd_define/baz')).toEqual('BAZ');
    });

    it("with AMD define no params", function() {
        lib.register("bar", function (define, require, module, exports) {
            define(function () {
                return 'BAR';
            });
        });

        expect(lib.require('bar')).toEqual('BAR');
    });

    it("with AMD define multiple modules", function() {
        lib.register("hans", function (define, require, module, exports) {
            define('hans_foo', [], function () {
                return 'FOO';
            });
            define('hans_bar', ['hans_foo'], function () {
                return 'BAR';
            });
        });

        lib.require('hans');

        expect(lib.require('hans_foo')).toEqual('FOO');
        expect(lib.require('hans_bar')).toEqual('BAR');
    });

    it("with CommonJS module.exports", function() {
        lib.register("with_module_exports", function (define, require, module, exports) {
            module.exports = {'Foo' : true};
        });

        expect(lib.require('with_module_exports')).toEqual({'Foo' : true});
    });

    it("with CommonJS exports", function() {
        lib.register("commonjs_exports/foo", "commonjs_exports", function(define, require, module, exports) {
            define([], function () {
                return 'commonjs_exports/foo';
            });
        });


        lib.register("commonjs_exports/bar", "commonjs_exports", function(define, require, module, exports) {
            var bar = require('./foo');

            exports.Foo = true;
        });

        expect(lib.require('commonjs_exports/bar')).toEqual({'Foo' : true});
    });

    it("with return property", function() {
        lib.register("henk", function (define, require, module, exports) {
            return 'HENK';
        });

        expect(lib.require('henk')).toEqual('HENK');
    });

    it("with relative require paths", function() {
        lib.register("this/is/relative/path", "this/is/relative", function(define, require, module, exports) {
            return 'HENK';
        });
        lib.register("this/is/spartha", "this/is", function(define, require, module, exports) {
            return require('./relative/path');
        });

        expect(lib.require('this/is/spartha')).toEqual('HENK');
    });

    it('register same module twice throws console message if console is available', function () {
        lib.register('a', function () {
            return {1: 'test'};
        });
        lib.register('a', function () {
            return {1: 'test2'};
        });
        expect(lib.require('a')).toEqual({1: 'test'});
        expect(console.warn).toHaveBeenCalled();
    });

    it("with relative require path dependencies", function() {
        lib.register("pathdeps", function (define, require, module, exports) {
            define('pathdeps/pathdepsfoo', [], function () {
                return 'FOO';
            });
            define('pathdeps/pathdepsbar', ['./pathdepsfoo'], function (foo) {
                return 'BAR';
            });
        });

        lib.require('pathdeps');

        expect(lib.require('pathdeps/pathdepsfoo')).toEqual('FOO');
        expect(lib.require('pathdeps/pathdepsbar')).toEqual('BAR');
    });

    it("with define inside module with no dependencies", function() {
        lib.register("pathdeps", function (define, require, module, exports) {
            define('pathdeps/pathdepsfoo', function () {
                return 'FOO';
            });
            define('pathdeps/pathdepsbar', ['./pathdepsfoo'], function (foo) {
                return 'BAR';
            });
        });

        lib.require('pathdeps');

        expect(lib.require('pathdeps/pathdepsfoo')).toEqual('FOO');
        expect(lib.require('pathdeps/pathdepsbar')).toEqual('BAR');
    });

    it("with relative paths", function() {
        lib.register("relfoo/bar", function(define, require, module, exports) {
            define([], function () {
                return 'BAR';
            });
        });
        lib.register("relfoo", function(define, require, module, exports) {
            define([], function () {
                return require('./bar');
            });
        });

        expect(lib.require('relfoo')).toEqual('BAR');
    });

    it("with unknown require", function() {
        try {
            lib.require('somemodule');
        } catch (e) {
            expect(e.message).toBe('Cannot find module "somemodule", did you define it?');
        }
    });
});

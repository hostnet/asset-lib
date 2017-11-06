var CleanCSS = require("clean-css");
var less = require("less");
var path = require('path');
var ts = require("typescript");
var UglifyJS = require("uglify-js");

(function () {
    var compilers = {
        TSC: function (filename, source) {
            var result = ts.transpileModule(source, {
                compilerOptions: {
                    inlineSourceMap: false,
                    skipLibCheck: true,
                    importHelpers: true,
                    target: ts.ScriptTarget.ES5,
                    module: ts.ModuleKind.CommonJS,
                    moduleResolution: ts.ModuleResolutionKind.NodeJs,
                    emitDecoratorMetadata: true,
                    experimentalDecorators: true
                }
            });

            return result.outputText;
        },
        LES: function (filename, source) {
            var css = null;
            less.render(source, {
                "filename": path.resolve(filename),
                "syncImport": true
            }, function (error, output) {
                if (null !== error) {
                    throw error.message + ' in ' + error.filename + ' on line ' + error.line;
                }
                css = output.css;
            });
            return css;
        },
        UGL: function (filename, source) {
            var result = UglifyJS.minify(source);
            if (result.error) {
                throw result.error;
            }

            return result.code;
        },
        CLE: function (filename, source) {
            var options = {/* options */},
                output = new CleanCSS(options).minify(source);
            return output.styles;
        }
    };

    module.exports = {
        TSC: 'TSC', // Typescript compiler
        LES: 'LES', // Less
        UGL: 'UGL', // Uglify
        CLE: 'CLE', // Clean CSS
        process: function (type, filename, message) {
            if (compilers.hasOwnProperty(type)) {
                return compilers[type](filename, message);
            }
            throw "Unknown type " + type;
        }
    };
}());

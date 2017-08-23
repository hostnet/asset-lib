var uglify = require("uglify-js");
var fs = require("fs");

var result = uglify.minify(process.argv[2]);

process.stdout.write(result.code);

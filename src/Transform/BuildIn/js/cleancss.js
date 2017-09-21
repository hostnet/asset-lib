var CleanCSS = require("clean-css");
var fs = require("fs");

var source = fs.readFileSync(process.argv[2]);
var options = { /* options */ };
var output = new CleanCSS(options).minify(source);

process.stdout.write(output.styles);

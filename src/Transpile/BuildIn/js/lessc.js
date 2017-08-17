var less = require("less");
var path = require('path');
var fs = require("fs");

var file = process.argv[2];
var source = fs.readFileSync(file);

less.render(source.toString(), {
    "sourceMap": {
        "sourceMapFileInline" : true
    },
    "filename": path.resolve(file)
}, function (error, output) {
    if (null !== error) {
        process.stdout.write(error.message);
        process.stdout.write("\n");

        process.exit(1);
    }

    process.stdout.write(output.css);
});

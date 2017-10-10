var less = require("less");
var path = require('path');

function compile(source) {
    less.render(source, {
        "filename": path.resolve(process.argv[2])
    }, function (error, output) {
        if (null !== error) {
            process.stdout.write(error.message);
            process.stdout.write("\n");

            process.exit(1);
        }

        process.stdout.write(output.css);
    });
}

var content = '';

process.stdin.resume();
process.stdin.on('data', function(buf) { content += buf.toString(); });
process.stdin.on('end', function() {
    // your code here
    compile(content);
});

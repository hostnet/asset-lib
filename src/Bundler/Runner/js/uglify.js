var UglifyJS = require("uglify-js");

function compile(source) {
    var result = UglifyJS.minify(source);

    if (result.error) {
        console.error(result.error);
        process.exit(1);
    }

    process.stdout.write(result.code);
}

var content = '';

process.stdin.resume();
process.stdin.on('data', function(buf) { content += buf.toString(); });
process.stdin.on('end', function() {
    // your code here
    compile(content);
});

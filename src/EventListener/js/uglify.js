var UglifyJS = require("uglify-js");

function compile(source) {
    var result = UglifyJS.minify(source);

    process.stdout.write(result.code);
}

var content = '';

process.stdin.resume();
process.stdin.on('data', function(buf) { content += buf.toString(); });
process.stdin.on('end', function() {
    // your code here
    compile(content);
});

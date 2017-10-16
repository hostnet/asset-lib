var CleanCSS = require("clean-css");

function compile(source) {
    var options = {/* options */};
    var output = new CleanCSS(options).minify(source);

    process.stdout.write(output.styles);
}

var content = '';

process.stdin.resume();
process.stdin.on('data', function(buf) { content += buf.toString(); });
process.stdin.on('end', function() {
    // your code here
    compile(content);
});

var path = require('path');
var processor = require("../../../Resources/processor");

function compile(source) {
    var fileName = path.resolve(process.argv[2]);
    process.stdout.write(processor.process(processor.TSC, fileName, source));
}

var content = '';

process.stdin.resume();
process.stdin.on('data', function(buf) { content += buf.toString(); });
process.stdin.on('end', function() {
    // your code here
    compile(content);
});

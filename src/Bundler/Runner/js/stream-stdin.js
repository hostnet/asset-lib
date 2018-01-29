/**
 * Streams stdin properly and calls the compiled method with the resulted content afterwards
 */
module.exports = function streamFunction(compile) {
    var content = '';

    // we need to split it on newlines so unicode characters can not be split in 2 data events.
    var stream = process.stdin.pipe(require('split')());
    stream.on('data', function (buf) {
        content += buf.toString() + '\n';
    });
    stream.on('end', function () {
        // your code here
        compile(content);
    });
};
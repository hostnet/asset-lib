var path = require('path');
var processor = require("../../../Resources/processor");

function compile(source) {
    var fileName = path.resolve(process.argv[2]);
    process.stdout.write(processor.process(processor.LES, fileName, source));
}

require('./stream-stdin')(compile);

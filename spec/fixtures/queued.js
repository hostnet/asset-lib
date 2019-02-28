let path = require('path'), builder = require('../../src/Builder/js/builder');

module.exports = function (file, config, queue) {
    if (file.name.endsWith('foo.js')) {
        queue.add(new builder.BuildableFile(path.join(__dirname, 'input', 'bar.js')));
    }

    return file;
};

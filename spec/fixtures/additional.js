let path = require('path');

module.exports = function (file) {
    if (file.name.endsWith('foo.js')) {
        file.addAdditionalFile('/bar.js', [path.join(__dirname, 'input', 'bar.js')]);
    }

    return file;
};

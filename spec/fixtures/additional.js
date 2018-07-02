module.exports = function (file) {
    if (file.name.endsWith('foo.js')) {
        file.addAdditionalFile('/bar.js', [__dirname + '/input/bar.js']);
    }

    return file;
};

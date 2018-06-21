let fs = require('fs'), zlib = require('zlib');

module.exports = function (file) {
    let content = zlib.deflateSync(file.content) || '';

    if (content.length > file.content.length) {
        return Promise.resolve();
    }

    return new Promise(function (resolve, reject) {
        fs.writeFile(file.name + ".gz", content, function (err) {
            if (err) reject(e);

            resolve();
        });
    });
};

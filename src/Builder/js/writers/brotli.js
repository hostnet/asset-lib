let fs = require('fs'), compress = require('brotli/compress');

module.exports = function (file) {
    let options = { mode: 0, quality: 11, lgwin: 22 };
    let content = compress(file.content, options);

    if (content === null || content.length > file.content.length) {
        return Promise.resolve();
    }

    return new Promise(function (resolve, reject) {
        fs.writeFile(file.name + ".br", content, function (err) {
            if (err) reject(e);

            resolve();
        });
    });
};

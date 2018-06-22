let fs = require('fs');

module.exports = function (file) {
    return new Promise(function (resolve, reject) {
        fs.writeFile(file.name, file.content, function (err) {
            if (err) reject(e);

            resolve();
        });
    });
};

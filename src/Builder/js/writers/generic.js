let fs = require('fs');

module.exports = function (file) {
    fs.writeFile(file.name, file.content, function (err) {
        if (err) throw err;
    });
};

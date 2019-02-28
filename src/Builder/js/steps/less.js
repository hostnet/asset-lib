let less = require("less");

module.exports = function (file) {
    return less.render(file.content.toString(), {
        filename: file.name,
        syncImport: true,
        relativeUrls: true,
        javascriptEnabled: true
    }).then((output) => {
        file.update(Buffer.from(output.css));

        return file;
    });
};

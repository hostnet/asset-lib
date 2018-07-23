let less = require("less");

module.exports = function (file) {
    let css = null;
    less.render(file.content.toString(), {
        filename: file.name,
        syncImport: true,
        relativeUrls: true,
        javascriptEnabled: true
    }, function (error, output) {
        if (null !== error) {
            throw error.message + ' in ' + error.filename + ' on line ' + error.line;
        }

        css = output.css;
    });

    return file.update(Buffer.from(css));
};

let less = require("less");

module.exports = function (file) {
    let css = null;
    less.render(file.content.toString(), {
        filename: file.name,
        syncImport: true
    }, function (error, output) {
        if (null !== error) {
            throw error.message + ' in ' + error.filename + ' on line ' + error.line;
        }
        css = output.css;
    });

    return {name: file.name, module: file.module, content: css};
};

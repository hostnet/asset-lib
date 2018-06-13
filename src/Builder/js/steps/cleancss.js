let CleanCSS = require("clean-css");

module.exports = function (file) {
    let output = new CleanCSS({level: 2}).minify(file.content);

    return {name: file.name, module: file.module, content: output.styles};
};

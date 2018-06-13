let UglifyJS = require("uglify-js");

module.exports = function (file) {
    let result = UglifyJS.minify(file.content);

    if (result.error) {
        throw result.error;
    }

    return {name: file.name, module: file.module, content: result.code};
};

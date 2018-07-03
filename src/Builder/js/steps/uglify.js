let UglifyJS = require("uglify-js");

module.exports = function (file) {
    let result = UglifyJS.minify(file.content);

    if (result.error) {
        throw result.error;
    }

    return file.update(Buffer.from(result.code));
};

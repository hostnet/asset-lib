let UglifyJS = require("uglify-js");

module.exports = function (file) {
    let result = UglifyJS.minify(file.content.toString());

    if (result.error) {
        return Promise.reject(result.error);
    }

    file.update(Buffer.from(result.code));

    return Promise.resolve(file);
};

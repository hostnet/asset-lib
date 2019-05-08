let Terser = require("terser");

module.exports = function (file) {
    let result = Terser.minify(file.content.toString());

    if (result.error) {
        throw result.error;
    }

    return file.update(Buffer.from(result.code));
};

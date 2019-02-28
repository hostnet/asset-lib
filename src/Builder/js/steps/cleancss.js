let CleanCSS = require("clean-css");

module.exports = function (file) {
    let output = new CleanCSS({level: 2}).minify(file.content);

    file.update(Buffer.from(output.styles));

    return Promise.resolve(file);
};

module.exports = function (file) {
    let content = "register(" + JSON.stringify(file.module) + ", " + JSON.stringify(file.parentPath) + ", function (define, require, module, exports) {\n" + file.content + "\n});\n";

    return file.update(Buffer.from(content));
};

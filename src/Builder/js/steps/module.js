module.exports = function (file) {
    return file.update(Buffer.from("register(" + JSON.stringify(file.module) + ", function (define, require, module, exports) {\n" + file.content + "\n});\n"));
};

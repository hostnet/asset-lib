module.exports = function (file) {
    return {
        name: file.name,
        module: file.module,
        content:"register(" + JSON.stringify(file.module) + ", function (define, require, module, exports) {\n" + file.content + "\n});\n"
    };
};

module.exports = function (file) {
    let args = JSON.stringify(file.module);

    if (file.module !== file.parentPath) {
        args += ", " + JSON.stringify(file.parentPath);
    }

    let content = "register(" + args + ", function (define, require, module, exports) {\n" + file.content + "\n});\n";

    file.update(Buffer.from(content));

    return Promise.resolve(file);
};

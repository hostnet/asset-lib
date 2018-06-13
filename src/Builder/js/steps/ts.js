let ts = require("typescript");

module.exports = function (file) {
    let result = ts.transpileModule(file.content.toString(), {
        compilerOptions: {
            inlineSourceMap: false,
            skipLibCheck: true,
            importHelpers: true,
            target: ts.ScriptTarget.ES5,
            module: ts.ModuleKind.CommonJS,
            moduleResolution: ts.ModuleResolutionKind.NodeJs,
            emitDecoratorMetadata: true,
            experimentalDecorators: true
        }
    });

    return {name: file.name, module: file.module, content: result.outputText};
};

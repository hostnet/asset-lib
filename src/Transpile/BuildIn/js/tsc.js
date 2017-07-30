var ts = require("typescript");
var fs = require("fs");

var source = fs.readFileSync(process.argv[2]);

var result = ts.transpileModule(source.toString(), {
    compilerOptions: {
        inlineSourceMap: true,
        skipLibCheck: true,
        target: ts.ScriptTarget.ES5,
        module: ts.ModuleKind.CommonJS,
        moduleResolution: ts.ModuleResolutionKind.NodeJs,
        experimentalDecorators: true
    }
});

process.stdout.write(result.outputText);

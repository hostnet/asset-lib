var ts = require("typescript");

function compile(source) {
    var result = ts.transpileModule(source, {
        compilerOptions: {
            inlineSourceMap: true,
            skipLibCheck: true,
            target: ts.ScriptTarget.ES5,
            module: ts.ModuleKind.AMD,
            moduleResolution: ts.ModuleResolutionKind.NodeJs,
            experimentalDecorators: true
        }
    });

    process.stdout.write(result.outputText);
}

var content = '';

process.stdin.resume();
process.stdin.on('data', function(buf) { content += buf.toString(); });
process.stdin.on('end', function() {
    // your code here
    compile(content);
});

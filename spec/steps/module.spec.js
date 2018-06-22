describe("module.js", function () {
    it('execute', function () {
        let step = require('../../src/Builder/js/steps/module');
        let result = step({
            name: 'foo.js',
            module: 'foo.js',
            content: "foobar();"
        });

        expect(result.name).toBe('foo.js');
        expect(result.module).toBe('foo.js');
        expect(result.content).toBe(
            'register("foo.js", function (define, require, module, exports) {\nfoobar();\n});\n'
        );
    });
});

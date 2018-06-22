describe("uglify.js", function () {
    it('execute', function () {
        let step = require('../../src/Builder/js/steps/uglify');
        let result = step({
            name: 'foo.js',
            module: 'foo.js',
            content: 'var A = /** @class */ (function () {\n' +
            '    function A() {\n' +
            '    }\n' +
            '    return A;\n' +
            '}());\n'
        });

        expect(result.name).toBe('foo.js');
        expect(result.module).toBe('foo.js');
        expect(result.content).toBe('var A=function(){};');
    });

    it('on error', function () {
        let step = require('../../src/Builder/js/steps/uglify');
        try {
            step({
                name: 'foo.js',
                module: 'foo.js',
                content: "{{{"
            });
        } catch (e) {
            expect(e.message).toBe("Unexpected token: eof (undefined)");
        }
    });
});

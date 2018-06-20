describe("ts.js", function () {
    it('execute', function () {
        let step = require('../../src/Builder/js/steps/ts');
        let result = step({
            name: 'foo.ts',
            module: 'foo.ts',
            content: "class A {}"
        });

        expect(result.name).toBe('foo.ts');
        expect(result.module).toBe('foo');
        expect(result.content).toBe(
            'var A = /** @class */ (function () {\n' +
            '    function A() {\n' +
            '    }\n' +
            '    return A;\n' +
            '}());\n'
        );
    });

    it('execute module', function () {
        let step = require('../../src/Builder/js/steps/ts');
        let result = step({
            name: 'foo.ts',
            module: 'foo/foo.ts',
            content: "class A {}"
        });

        expect(result.name).toBe('foo.ts');
        expect(result.module).toBe('foo/foo');
        expect(result.content).toBe(
            'var A = /** @class */ (function () {\n' +
            '    function A() {\n' +
            '    }\n' +
            '    return A;\n' +
            '}());\n'
        );
    });
});

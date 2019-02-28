let builder = require('../../src/Builder/js/builder');

describe("ts.js", function () {
    it('execute', function () {
        let step = require('../../src/Builder/js/steps/ts');
        return step(new builder.File('foo.ts', 'foo.ts', Buffer.from("class A {}"))).then((result) => {
            expect(result.name).toBe('foo.ts');
            expect(result.module).toBe('foo');
            expect(result.content.toString().replace(/[\r]/g, '')).toBe(
                'var A = /** @class */ (function () {\n' +
                '    function A() {\n' +
                '    }\n' +
                '    return A;\n' +
                '}());\n'
            );
        });

    });

    it('execute module', function () {
        let step = require('../../src/Builder/js/steps/ts');
        return step(new builder.File('foo.ts', 'foo/foo.ts', Buffer.from("class A {}"))).then((result) => {
            expect(result.name).toBe('foo.ts');
            expect(result.module).toBe('foo/foo');
            expect(result.content.toString().replace(/[\r]/g, '')).toBe(
                'var A = /** @class */ (function () {\n' +
                '    function A() {\n' +
                '    }\n' +
                '    return A;\n' +
                '}());\n'
            );
        });
    });
});

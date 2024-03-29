let builder = require('../../src/Builder/js/build');

describe("ts.js", function () {
    it('execute', function () {
        let step = require('../../src/Builder/js/steps/ts');
        let result = step(new builder.File('foo.ts', 'foo.ts', Buffer.from("class A {}")));

        expect(result.name).toBe('foo.ts');
        expect(result.module).toBe('foo');
        expect(result.content.toString()).toBe('class A {\n}\n');
    });

    it('execute module', function () {
        let step = require('../../src/Builder/js/steps/ts');
        let result = step(new builder.File('foo.ts', 'foo/foo.ts', Buffer.from('class A {}')));

        expect(result.name).toBe('foo.ts');
        expect(result.module).toBe('foo/foo');
        expect(result.content.toString()).toBe('class A {\n}\n');
    });
});

let builder = require('../../src/Builder/js/builder');

describe("less.js", function () {
    it('execute', function () {
        let step = require('../../src/Builder/js/steps/less');
        return step(new builder.File(
            'foo.less',
            'foo.less',
            Buffer.from("body{ background: red; a { font-size: 12px;}}")
        )).then((result) => {
            expect(result.name).toBe('foo.less');
            expect(result.module).toBe('foo.less');
            expect(result.content.toString()).toBe('body {\n  background: red;\n}\nbody a {\n  font-size: 12px;\n}\n');
        });

    });

    it('execute with JS', function () {
        let step = require('../../src/Builder/js/steps/less');
        return step(new builder.File(
            'foo.less',
            'foo.less',
            Buffer.from("@foo: `42`; body { margin: '@{foo}px'; }")
        )).then((result) => {
            expect(result.name).toBe('foo.less');
            expect(result.module).toBe('foo.less');
            expect(result.content.toString()).toBe("body {\n  margin: '42px';\n}\n");
        });

    });

    it('on error', function () {
        let step = require('../../src/Builder/js/steps/less');
        return step(new builder.File(
            'foo.less',
            'foo.less',
            Buffer.from("{{{")
        )).catch((e) => {
            expect(e.message).toBe("Unrecognised input");
        });
    });
});

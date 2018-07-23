let builder = require('../../src/Builder/js/build');

describe("less.js", function () {
    it('execute', function () {
        let step = require('../../src/Builder/js/steps/less');
        let result = step(new builder.File(
            'foo.less',
            'foo.less',
            Buffer.from("body{ background: red; a { font-size: 12px;}}")
        ));

        expect(result.name).toBe('foo.less');
        expect(result.module).toBe('foo.less');
        expect(result.content.toString()).toBe('body {\n  background: red;\n}\nbody a {\n  font-size: 12px;\n}\n');
    });

    it('execute with JS', function () {
        let step = require('../../src/Builder/js/steps/less');
        let result = step(new builder.File(
            'foo.less',
            'foo.less',
            Buffer.from("@foo: `42`; body { margin: '@{foo}px'; }")
        ));

        expect(result.name).toBe('foo.less');
        expect(result.module).toBe('foo.less');
        expect(result.content.toString()).toBe("body {\n  margin: '42px';\n}\n");
    });

    it('on error', function () {
        let step = require('../../src/Builder/js/steps/less');
        try {
            step(new builder.File(
                'foo.less',
                'foo.less',
                Buffer.from("{{{")
            ));
        } catch (e) {
            expect(e).toBe("Unrecognised input in foo.less on line 1");
        }
    });
});

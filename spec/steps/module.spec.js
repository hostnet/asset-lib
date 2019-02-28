let builder = require('../../src/Builder/js/builder');

describe("module.js", function () {
    it('execute', function () {
        let step = require('../../src/Builder/js/steps/module');
        return step(new builder.File('foo.js', 'foo.js', Buffer.from("foobar();"), '', 'foo')).then((result) => {
            expect(result.name).toBe('foo.js');
            expect(result.module).toBe('foo.js');
            expect(result.content.toString()).toBe(
                'register("foo.js", "foo", function (define, require, module, exports) {\nfoobar();\n});\n'
            );
        });

    });

    it('execute same as parent', function () {
        let step = require('../../src/Builder/js/steps/module');
        return step(new builder.File('foo.js', 'foo/bar', Buffer.from("foobar();"), '', 'foo/bar')).then((result) => {
            expect(result.name).toBe('foo.js');
            expect(result.module).toBe('foo/bar');
            expect(result.content.toString()).toBe(
                'register("foo/bar", function (define, require, module, exports) {\nfoobar();\n});\n'
            );
        });
    });
});

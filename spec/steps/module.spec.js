let builder = require('../../src/Builder/js/build');

describe("module.js", function () {
    it('execute', function () {
        let step = require('../../src/Builder/js/steps/module');
        let result = step(new builder.File('foo.js', 'foo.js', Buffer.from("foobar();"), '', 'foo'));

        expect(result.name).toBe('foo.js');
        expect(result.module).toBe('foo.js');
        expect(result.content.toString()).toBe(
            'register("foo.js", "foo", function (define, require, module, exports) {\nfoobar();\n});\n'
        );
    });
});

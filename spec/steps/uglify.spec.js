let builder = require('../../src/Builder/js/build');

describe("uglify.js", function () {
    it('execute', function () {
        let step = require('../../src/Builder/js/steps/uglify');
        let result = step(new builder.File(
            'foo.js',
            'foo.js',
            Buffer.from(
                'var A = /** @class */ (function () {\n' +
                '    function A() {\n' +
                '    }\n' +
                '    return A;\n' +
                '}());\n'
            )
        ));

        expect(result.name).toBe('foo.js');
        expect(result.module).toBe('foo.js');
        expect(result.content.toString()).toBe('var A=function(){};');
    });

    it('on error', function () {
        let step = require('../../src/Builder/js/steps/uglify');
        try {
            step(new builder.File('foo.js', 'foo.js', Buffer.from("{{{")));
        } catch (e) {
            expect(e.message).toBe("Unexpected token: eof (undefined)");
        }
    });
});

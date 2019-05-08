let builder = require('../../src/Builder/js/build');

describe("uglify.js", function () {
    it('execute', function () {
        let step = require('../../src/Builder/js/steps/uglify');
        let result = step(new builder.File(
            'foo.js',
            'foo.js',
            Buffer.from('function add(first, second) { const useless = 0; return first + second; }')
        ));

        expect(result.name).toBe('foo.js');
        expect(result.module).toBe('foo.js');
        expect(result.content.toString()).toBe('function add(n,d){return n+d}');
    });

    it('on error', function () {
        let step = require('../../src/Builder/js/steps/uglify');
        try {
            step(new builder.File('foo.js', 'foo.js', Buffer.from("{{{")));
            fail();
        } catch (e) {
            expect(e.message).toContain("Unexpected token: eof");
        }
    });
});

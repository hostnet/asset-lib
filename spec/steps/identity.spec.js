let builder = require('../../src/Builder/js/build');

describe("identity.js", function () {
    it('execute', function () {
        let step = require('../../src/Builder/js/steps/identity');
        let result = step(new builder.File('foo.ext', 'foo.ext', Buffer.from('foobar')));

        expect(result.name).toBe('foo.ext');
        expect(result.module).toBe('foo.ext');
        expect(result.content.toString()).toBe('foobar');
    });
});

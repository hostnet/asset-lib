let builder = require('../../src/Builder/js/builder');

describe("identity.js", function () {
    it('execute', function () {
        let step = require('../../src/Builder/js/steps/identity');
        return step(new builder.File('foo.ext', 'foo.ext', Buffer.from('foobar'))).then((result) => {
            expect(result.name).toBe('foo.ext');
            expect(result.module).toBe('foo.ext');
            expect(result.content.toString()).toBe('foobar');
        });
    });
});

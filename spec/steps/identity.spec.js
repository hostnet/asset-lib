describe("identity.js", function () {
    it('execute', function () {
        let step = require('../../src/Builder/js/steps/identity');
        let result = step({
            name: 'foo.ext',
            module: 'foo.ext',
            content: "foobar"
        });

        expect(result.name).toBe('foo.ext');
        expect(result.module).toBe('foo.ext');
        expect(result.content).toBe('foobar');
    });
});

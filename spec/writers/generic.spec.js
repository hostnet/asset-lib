let fs = require('fs');

describe("generic.js", function () {
    it('write', function () {
        let writer = require('../../src/Builder/js/writers/generic');

        writer({
            name: __dirname + '/generic.output.js',
            module: 'foo.js',
            content: "foobar"
        })
            .then(() => {
                expect(fs.existsSync(__dirname + '/generic.output.js')).toBe(true);
                expect(fs.readFileSync(__dirname + '/generic.output.js').toString()).toBe('foobar');
            })
            .then(
                () => fs.unlinkSync(__dirname + '/generic.output.js'),
                () => fs.unlinkSync(__dirname + '/generic.output.js')
            );
    });
});

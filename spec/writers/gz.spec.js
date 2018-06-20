let fs = require('fs');

describe("gz.js", function () {
    it('write', function () {
        let writer = require('../../src/Builder/js/writers/gz');

        writer({
            name: __dirname + '/gz.output.js',
            module: 'foo.js',
            content: Buffer.from("XXXXXXXXXXYYYYYYYYY", 'utf8')
        })
            .then(() => {
                expect(fs.existsSync(__dirname + '/gz.output.js.gz')).toBe(true);
                expect(fs.readFileSync(__dirname + '/gz.output.js.gz'))
                    .toEqual(Buffer.from([120, 156, 139, 136, 128, 129, 72, 24, 0, 0, 65, 144, 6, 146]));
            })
            .then(
                () => fs.unlinkSync(__dirname + '/gz.output.js.gz'),
                () => fs.unlinkSync(__dirname + '/gz.output.js.gz')
            );
    });

    it('write output too big', function (done) {
        let writer = require('../../src/Builder/js/writers/gz');

        writer({
            name: __dirname + '/gz-big.output.js',
            module: 'foo.js',
            content: Buffer.from("A", 'utf8')
        })
            .then(() => {
                expect(fs.existsSync(__dirname + '/gz-big.output.js.br')).toBe(false);
            })
            .then(() => done(), () => done());
    });
});

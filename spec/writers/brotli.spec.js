let fs = require('fs');

describe("brotli.js", function () {
    it('write', function (done) {
        let writer = require('../../src/Builder/js/writers/brotli');

        writer({
            name: __dirname + '/brotli.output.js',
            module: 'foo.js',
            content: Buffer.from("XXXXXXXXXXYYYYYYYYY", 'utf8')
        })
            .then(() => {
                expect(fs.existsSync(__dirname + '/brotli.output.js.br')).toBe(true);
                expect(fs.readFileSync(__dirname + '/brotli.output.js.br'))
                    .toEqual(Buffer.from([27, 18, 0, 0, 164, 176, 178, 106, 4, 18, 2, 8, 30]));
            })
            .finally(() => fs.unlinkSync(__dirname + '/brotli.output.js.br'))
            .finally(() => done());
    });

    it('write output too big', function (done) {
        let writer = require('../../src/Builder/js/writers/brotli');

        writer({
            name: __dirname + '/brotli-big.output.js',
            module: 'foo.js',
            content: Buffer.from("A", 'utf8')
        })
            .then(() => {
                expect(fs.existsSync(__dirname + '/brotli-big.output.js.br')).toBe(false);
            })
            .finally(() => done());
    });
});

var processor = require('../src/Resources/processor');

describe("processor", function () {
    it("constants", function () {
        expect(processor.TSC).toEqual("TSC");
        expect(processor.LES).toEqual("LES");
        expect(processor.UGL).toEqual("UGL");
        expect(processor.CLE).toEqual("CLE");
        expect(processor.BRO).toEqual("BRO");
    });

    it("process unknown", function () {
        expect(function () { processor.process("A", '.'); }).toThrow("Unknown type A");
    });

    it("process tsc", function () {
        expect(processor.process(processor.TSC, 'a.ts', "alert('hi');")).toEqual("alert('hi');\n");
    });

    it("process less", function () {
        var expected = ".bla {\n  color: red;\n}\n";
        expect(processor.process(processor.LES, 'a.css', ".bla { color: red; }")).toEqual(expected);
    });

    it("process brotli", function () {
        var expected = ".bla {\n  color: red;\n}\n";
        expect(processor.process(processor.BRO, __filename, '')).toEqual(jasmine.any(String));
    });

    it("process less error", function () {
        try {
            processor.process(processor.LES, 'a.css', ".bla {");
        } catch (e) {
            expect(e).toMatch("Unrecognised input");
            return;
        }

        expect(true).toEqual(false);
    });

    it("process uglify", function () {
        var expected = "alert(\"hi\");";
        expect(processor.process(processor.UGL, 'a.js', "/* bla */alert('hi');")).toEqual(expected);
    });

    it("process uglify error", function () {
        try {
            processor.process(processor.UGL, 'a.js', "alert('hi);");
        } catch (e) {
            expect(e.message).toMatch("Unterminated string constant");
            return;
        }
        expect(true).toEqual(false);
    });

    it("process clean css", function () {
        var expected = '.bla{color:red}';
        expect(processor.process(processor.CLE, 'a.css', "/* bla */.bla { color: red; }")).toEqual(expected);
    });
});

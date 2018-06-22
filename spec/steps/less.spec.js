describe("less.js", function () {
    it('execute', function () {
        let step = require('../../src/Builder/js/steps/less');
        let result = step({
            name: 'foo.less',
            module: 'foo.less',
            content: "body{ background: red; a { font-size: 12px;}}"
        });

        expect(result.name).toBe('foo.less');
        expect(result.module).toBe('foo.less');
        expect(result.content).toBe('body {\n  background: red;\n}\nbody a {\n  font-size: 12px;\n}\n');
    });

    it('on error', function () {
        let step = require('../../src/Builder/js/steps/less');
        try {
            step({
                name: 'foo.less',
                module: 'foo.less',
                content: "{{{"
            });
        } catch (e) {
            expect(e).toBe("Unrecognised input in foo.less on line 1");
        }
    });
});

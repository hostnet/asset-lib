describe("cleancss.js", function () {
   it('execute', function () {
       let step = require('../../src/Builder/js/steps/cleancss');
       let result = step({
           name: 'foo.css',
           module: 'foo.css',
           content: "body\n\n\n\n{\n    background: red;\n\n\n}\n\n"
       });

       expect(result.name).toBe('foo.css');
       expect(result.module).toBe('foo.css');
       expect(result.content).toBe('body{background:red}');
   });
});

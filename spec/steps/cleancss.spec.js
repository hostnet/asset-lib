let builder = require('../../src/Builder/js/build');

describe("cleancss.js", function () {
   it('execute', function () {
       let step = require('../../src/Builder/js/steps/cleancss');
       let result = step(new builder.File(
           'foo.css',
           'foo.css',
           Buffer.from("body\n\n\n\n{\n    background: red;\n\n\n}\n\n")
       ));

       expect(result.name).toBe('foo.css');
       expect(result.module).toBe('foo.css');
       expect(result.content.toString()).toBe('body{background:red}');
   });
});

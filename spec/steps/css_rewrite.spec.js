let builder = require('../../src/Builder/js/build');
let path = require('path');

describe("css_rewrite.js", function () {
    let step = require('../../src/Builder/js/steps/css_rewrite');

    it('execute', function () {
        let content = Buffer.from(
            "body{ background: red;} @font-face {font-family: myFirstFont; src: url('sansation_light.woff');}"
        );
        let result = step(
            new builder.File('foo.css', 'foo.css', content, 'foo.css'),
            {paths: {out: '/foo/bar/', root: '/foo/'}}
        );

        expect(result.name).toBe('foo.css');
        expect(result.module).toBe('foo.css');

        expect(result.additionalFiles.length).toBe(1);
        expect(result.additionalFiles[0].outputFile).toBe('/foo/bar/fonts/sansation_light.woff'.replace(/\//g, path.sep));
        expect(result.additionalFiles[0].inputFiles.length).toBe(1);

        expect(result.additionalFiles[0].inputFiles[0].path).toBe('/foo/sansation_light.woff'.replace(/\//g, path.sep));
        expect(result.additionalFiles[0].inputFiles[0].extension).toBe('.woff');
        expect(result.additionalFiles[0].inputFiles[0].needsRebuild).toBe(true);
        expect(result.additionalFiles[0].inputFiles[0].skipFileSteps).toBe(false);

        expect(result.content.toString()).toBe(
            'body{ background: red;} @font-face {font-family: myFirstFont; src: url("fonts/sansation_light.woff");}'
        );
    });

    it('execute double quotes', function () {
        let content = Buffer.from(
            "body{ background: red;} @font-face {font-family: myFirstFont; src: url(\"sansation_light.woff\");}"
        );
        let result = step(
            new builder.File('foo.css', 'foo.css', content, 'foo.css'),
            {paths: {out: '/foo/bar/', root: '/foo/'}}
        );

        expect(result.name).toBe('foo.css');
        expect(result.module).toBe('foo.css');

        expect(result.additionalFiles.length).toBe(1);
        expect(result.additionalFiles[0].outputFile).toBe('/foo/bar/fonts/sansation_light.woff'.replace(/\//g, path.sep));
        expect(result.additionalFiles[0].inputFiles.length).toBe(1);

        expect(result.additionalFiles[0].inputFiles[0].path).toBe('/foo/sansation_light.woff'.replace(/\//g, path.sep));
        expect(result.additionalFiles[0].inputFiles[0].extension).toBe('.woff');
        expect(result.additionalFiles[0].inputFiles[0].needsRebuild).toBe(true);
        expect(result.additionalFiles[0].inputFiles[0].skipFileSteps).toBe(false);

        expect(result.content.toString()).toBe(
            'body{ background: red;} @font-face {font-family: myFirstFont; src: url("fonts/sansation_light.woff");}'
        );
    });

    it('execute nested', function () {
        let content = Buffer.from(
            "@font-face {font-family: a; src: url(\"../../some/folder/fonts/font.woff\");}"
        );
        let result = step(
            new builder.File('assets/foo/bar.less', 'assets/foo/bar.less', content, 'foo/bar.less'),
            {paths: {out: "public/dev/", root: "/foo/bar/"}}
        );

        expect(result.name).toBe('assets/foo/bar.less');
        expect(result.module).toBe('assets/foo/bar.less');

        expect(result.additionalFiles.length).toBe(1);
        expect(result.additionalFiles[0].outputFile).toBe('public/dev/fonts/font.woff'.replace(/\//g, path.sep));
        expect(result.additionalFiles[0].inputFiles.length).toBe(1);

        expect(result.additionalFiles[0].inputFiles[0].path).toBe('/foo/bar/some/folder/fonts/font.woff'.replace(/\//g, path.sep));
        expect(result.additionalFiles[0].inputFiles[0].extension).toBe('.woff');
        expect(result.additionalFiles[0].inputFiles[0].needsRebuild).toBe(true);
        expect(result.additionalFiles[0].inputFiles[0].skipFileSteps).toBe(false);

        expect(result.content.toString()).toBe(
            '@font-face {font-family: a; src: url("../fonts/font.woff");}'
        );
    });

    it('execute with absolute font path', function () {
        let content = Buffer.from(
            "body{ background: red;} @font-face {font-family: myFirstFont; src: url('/fonts/sansation_light.woff');}"
        );
        let result = step(new builder.File('foo.css', 'foo.css', content), {paths: {out: '/foo/bar/', root: '/foo/'}});

        expect(result.name).toBe('foo.css');
        expect(result.module).toBe('foo.css');
        expect(result.additionalFiles.length).toBe(0);
        expect(result.content.toString()).toBe(content.toString());
    });

    it('execute with hash', function () {
        let content = Buffer.from(
            "@font-face {font-family: myFirstFont; src: url('sansation_light.svg#iconpicker');}"
        );
        let result = step(
            new builder.File('foo.css', 'foo.css', content, 'foo.css'),
            {paths: {out: '/foo/bar/', root: '/foo/'}}
        );

        expect(result.name).toBe('foo.css');
        expect(result.module).toBe('foo.css');

        expect(result.additionalFiles.length).toBe(1);
        expect(result.additionalFiles[0].outputFile).toBe('/foo/bar/fonts/sansation_light.svg'.replace(/\//g, path.sep));
        expect(result.additionalFiles[0].inputFiles.length).toBe(1);

        expect(result.additionalFiles[0].inputFiles[0].path).toBe('/foo/sansation_light.svg'.replace(/\//g, path.sep));
        expect(result.additionalFiles[0].inputFiles[0].extension).toBe('.svg');
        expect(result.additionalFiles[0].inputFiles[0].needsRebuild).toBe(true);
        expect(result.additionalFiles[0].inputFiles[0].skipFileSteps).toBe(false);

        expect(result.content.toString()).toBe(
            '@font-face {font-family: myFirstFont; src: url("fonts/sansation_light.svg#iconpicker");}'
        );
    });

    it('execute with query', function () {
        let content = Buffer.from(
            "@font-face {font-family: myFirstFont; src: url('sansation_light.svg?90190138');}"
        );
        let result = step(
            new builder.File('foo.css', 'foo.css', content, 'foo.css'),
            {paths: {out: '/foo/bar/', root: '/foo/'}}
        );

        expect(result.name).toBe('foo.css');
        expect(result.module).toBe('foo.css');

        expect(result.additionalFiles.length).toBe(1);
        expect(result.additionalFiles[0].outputFile).toBe('/foo/bar/fonts/sansation_light.svg'.replace(/\//g, path.sep));
        expect(result.additionalFiles[0].inputFiles.length).toBe(1);

        expect(result.additionalFiles[0].inputFiles[0].path).toBe('/foo/sansation_light.svg'.replace(/\//g, path.sep));
        expect(result.additionalFiles[0].inputFiles[0].extension).toBe('.svg');
        expect(result.additionalFiles[0].inputFiles[0].needsRebuild).toBe(true);
        expect(result.additionalFiles[0].inputFiles[0].skipFileSteps).toBe(false);

        expect(result.content.toString()).toBe(
            '@font-face {font-family: myFirstFont; src: url("fonts/sansation_light.svg?90190138");}'
        );
    });

    it('execute with hash and query', function () {
        let content = Buffer.from(
            "@font-face {font-family: myFirstFont; src: url('sansation_light.svg?90190138#iconpicker');}"
        );
        let result = step(
            new builder.File('foo.css', 'foo.css', content, 'foo.css'),
            {paths: {out: '/foo/bar/', root: '/foo/'}}
        );

        expect(result.name).toBe('foo.css');
        expect(result.module).toBe('foo.css');

        expect(result.additionalFiles.length).toBe(1);
        expect(result.additionalFiles[0].outputFile).toBe('/foo/bar/fonts/sansation_light.svg'.replace(/\//g, path.sep));
        expect(result.additionalFiles[0].inputFiles.length).toBe(1);

        expect(result.additionalFiles[0].inputFiles[0].path).toBe('/foo/sansation_light.svg'.replace(/\//g, path.sep));
        expect(result.additionalFiles[0].inputFiles[0].extension).toBe('.svg');
        expect(result.additionalFiles[0].inputFiles[0].needsRebuild).toBe(true);
        expect(result.additionalFiles[0].inputFiles[0].skipFileSteps).toBe(false);

        expect(result.content.toString()).toBe(
            '@font-face {font-family: myFirstFont; src: url("fonts/sansation_light.svg?90190138#iconpicker");}'
        );
    });
});

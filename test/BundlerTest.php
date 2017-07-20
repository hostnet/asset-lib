<?php
namespace PhpUnit;

use PHPUnit\Framework\TestCase;

class BundlerTest extends TestCase
{
    /**
     * @dataProvider commandProvider
     */
    public function testImportTypes(string $expected_file, string $input)
    {
        // Make sure we are in the root folder.
        chdir(__DIR__ . '/fixtures');

        // Command to run.
        $cmd = realpath(__DIR__ . '/../bin/bundler');

        self::assertStringEqualsFile(__DIR__ . '/' . $expected_file, `$cmd $input`);
    }

    public function commandProvider()
    {
        return [
            ['expected.less-import-syntax.txt', 'less/import-syntax/main.less'],
            ['expected.js-require-syntax.txt', 'js/require-syntax/main.js'],
            ['expected.ts-import-syntax.txt', 'ts/import-syntax/main.ts'],
        ];
    }
}

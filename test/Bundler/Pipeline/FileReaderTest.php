<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler\Pipeline;

use Hostnet\Component\Resolver\File;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Bundler\Pipeline\FileReader
 */
class FileReaderTest extends TestCase
{
    public function testGeneric()
    {
        $reader = new FileReader(__DIR__);

        self::assertEquals("console.log('foobar');\n", $reader->read(new File('fixtures/input.js')));
    }
}

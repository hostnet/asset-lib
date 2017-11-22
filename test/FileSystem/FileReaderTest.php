<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\FileSystem;

use Hostnet\Component\Resolver\File;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\FileSystem\FileReader
 */
class FileReaderTest extends TestCase
{
    public function testGeneric()
    {
        $reader = new FileReader(__DIR__);

        self::assertEquals("console.log('foobar');\n", $reader->read(new File('input.js')));
    }
}

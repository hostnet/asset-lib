<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\FileSystem;

use Hostnet\Component\Resolver\File;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\FileSystem\FileWriter
 */
class FileWriterTest extends TestCase
{
    public function testGeneric()
    {
        $writer = new FileWriter(__DIR__);
        $writer->write(new File('output/foobar.txt'), 'foobar');

        self::assertSame('foobar', file_get_contents(__DIR__ . '/output/foobar.txt'));
    }

    protected function tearDown()
    {
        unlink(__DIR__ . '/output/foobar.txt');
        rmdir(__DIR__ . '/output');
    }
}

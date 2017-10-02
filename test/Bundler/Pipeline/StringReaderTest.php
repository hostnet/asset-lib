<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler\Pipeline;

use Hostnet\Component\Resolver\File;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Bundler\Pipeline\StringReader
 */
class StringReaderTest extends TestCase
{
    public function testGeneric()
    {
        $reader = new StringReader('foobar');

        self::assertEquals('foobar', $reader->read(new File('foobar.js')));
    }
}

<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler;

use Hostnet\Component\Resolver\Bundler\Pipeline\ReaderInterface;
use Hostnet\Component\Resolver\File;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Bundler\ContentItem
 */
class ContentItemTest extends TestCase
{
    public function testGeneric()
    {
        $file = new File('foo.foo');

        $reader = $this->prophesize(ReaderInterface::class);
        $reader->read($file)->willReturn('foobar')->shouldBeCalledTimes(1);

        $item = new ContentItem($file, 'foo', $reader->reveal());

        self::assertEquals(ContentState::UNPROCESSED, $item->getState()->current());
        self::assertEquals('foo', $item->getState()->extension());
        self::assertEquals('foobar', $item->getContent());
        self::assertEquals('foobar', $item->getContent());
        self::assertEquals('foo', $item->module_name);

        $item->transition(ContentState::READY, 'barbaz', 'bar', 'henk');

        self::assertEquals(ContentState::READY, $item->getState()->current());
        self::assertEquals('bar', $item->getState()->extension());
        self::assertEquals('barbaz', $item->getContent());
        self::assertEquals('henk', $item->module_name);
    }
}

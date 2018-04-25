<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Event;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\StringReader;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Event\AssetEvent
 */
class AssetEventTest extends TestCase
{
    public function testGeneric()
    {
        $item  = new ContentItem(new File('foo.bar'), 'foo', new StringReader('foobar'));
        $event = new AssetEvent($item);

        self::assertSame($item, $event->getItem());

        $new_item = new ContentItem(new File('baz.bar'), 'baz', new StringReader('barbaz'));

        $event->setItem($new_item);

        self::assertSame($new_item, $event->getItem());
    }
}

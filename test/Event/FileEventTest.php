<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Event;

use Hostnet\Component\Resolver\File;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Event\FileEvent
 */
class FileEventTest extends TestCase
{
    public function testGeneric()
    {
        $file  = new File('foo.bar');
        $event = new FileEvent($file, 'foobar');

        self::assertSame($file, $event->getFile());
        self::assertSame('foobar', $event->getContent());

        $event->setContent('barbaz');

        self::assertSame('barbaz', $event->getContent());
    }
}

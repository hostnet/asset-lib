<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\FileSystem;

use Hostnet\Component\Resolver\Event\FileEvent;
use Hostnet\Component\Resolver\Event\FileEvents;
use Hostnet\Component\Resolver\File;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @covers \Hostnet\Component\Resolver\FileSystem\FileWriter
 */
class FileWriterTest extends TestCase
{
    public function testGeneric()
    {
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $dispatcher->dispatch(FileEvents::PRE_WRITE, Argument::type(FileEvent::class))->shouldBeCalled();
        $dispatcher->dispatch(FileEvents::POST_WRITE, Argument::type(FileEvent::class))->shouldBeCalled();

        $writer = new FileWriter($dispatcher->reveal(), __DIR__);
        $writer->write(new File('output/foobar.txt'), 'foobar');

        self::assertSame('foobar', file_get_contents(__DIR__ . '/output/foobar.txt'));

        $writer->write(new File(__DIR__ . '/output/baz.txt'), 'baz');
        self::assertSame('baz', file_get_contents(__DIR__ . '/output/baz.txt'));
    }

    public function testChangeContentWithListener()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(FileEvents::PRE_WRITE, function (FileEvent $e) {
            $e->setContent('barbaz');
        });

        $writer = new FileWriter($dispatcher, __DIR__);
        $writer->write(new File('output/foobar.txt'), 'foobar');

        self::assertSame('barbaz', file_get_contents(__DIR__ . '/output/foobar.txt'));
    }

    protected function tearDown()
    {
        unlink(__DIR__ . '/output/foobar.txt');
        if (file_exists(__DIR__ . '/output/baz.txt')) {
            unlink(__DIR__ . '/output/baz.txt');
        }
        rmdir(__DIR__ . '/output');
    }
}

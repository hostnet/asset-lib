<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\EventListener;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\Runner\RunnerInterface;
use Hostnet\Component\Resolver\Bundler\Runner\RunnerType;
use Hostnet\Component\Resolver\Event\FileEvent;
use Hostnet\Component\Resolver\File;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @covers \Hostnet\Component\Resolver\EventListener\BrotliListener
 */
class BrotliListenerTest extends TestCase
{
    public function testOnPostWrite()
    {
        $file     = new File(__FILE__);
        $contents = file_get_contents(__FILE__);

        $runner = $this->prophesize(RunnerInterface::class);
        $runner->execute(
            RunnerType::BROTLI,
            Argument::that(function (ContentItem $item) use ($contents, $file) {
                self::assertEquals($contents, $item->getContent());
                self::assertEquals($file, $item->file);
                return true;
            })
        )->willReturn('brotli.js');
        $dispatcher   = new EventDispatcher();
        $project_root = __DIR__;

        $brotli_listener = new BrotliListener(
            $runner->reveal(),
            $dispatcher,
            $project_root
        );
        @unlink(__FILE__ . '.br');
        try {
            $brotli_listener->onPostWrite(new FileEvent($file, $contents));
            self::assertTrue(file_exists(__FILE__ . '.br'));
        } finally {
            @unlink(__FILE__ . '.br');
        }
    }
}

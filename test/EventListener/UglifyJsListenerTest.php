<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\EventListener;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Bundler\Runner\RunnerInterface;
use Hostnet\Component\Resolver\Bundler\Runner\RunnerType;
use Hostnet\Component\Resolver\Event\AssetEvent;
use Hostnet\Component\Resolver\Event\FileEvent;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\StringReader;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\EventListener\UglifyJsListener
 */
class UglifyJsListenerTest extends TestCase
{
    private $runner;

    /**
     * @var UglifyJsListener
     */
    private $uglify_js_listener;

    protected function setUp()
    {
        $this->runner = $this->prophesize(RunnerInterface::class);

        $this->uglify_js_listener = new UglifyJsListener($this->runner->reveal());
    }

    public function testOnPreWrite()
    {
        $file  = new File('foobar.js');
        $item  = new ContentItem($file, 'foobar.js', new StringReader('foobar'));
        $event = new FileEvent($file, 'foobar');

        $this->runner->execute(RunnerType::UGLIFY, $item)->willReturn('uglify.js');

        $this->uglify_js_listener->onPreWrite($event);

        self::assertContains('uglify.js', $event->getContent());
    }

    public function testOnPreWriteNotJs()
    {
        $file  = new File('foobar.css');
        $event = new FileEvent($file, 'foobar');

        $this->runner->execute()->shouldNotBeCalled();

        $this->uglify_js_listener->onPreWrite($event);

        self::assertContains('foobar', $event->getContent());
    }
}

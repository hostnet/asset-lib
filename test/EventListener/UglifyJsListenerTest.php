<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\EventListener;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Bundler\Runner\UglifyJsRunner;
use Hostnet\Component\Resolver\Event\AssetEvent;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\StringReader;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
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
        $this->runner = $this->prophesize(UglifyJsRunner::class);

        $this->uglify_js_listener = new UglifyJsListener($this->runner->reveal());
    }

    public function testOnPreWrite()
    {
        $item = new ContentItem(new File('foobar.js'), 'foobar.js', new StringReader(''));
        $item->transition(ContentState::PROCESSED, 'foobar');

        $this->runner->execute($item)->willReturn('uglify.js');

        $this->uglify_js_listener->onPreWrite(new AssetEvent($item));

        self::assertSame(ContentState::PROCESSED, $item->getState()->current());
        self::assertContains('uglify.js', $item->getContent());
    }

    public function testOnPreWriteNotJs()
    {
        $item = new ContentItem(new File('foobar.css'), 'foobar.css', new StringReader(''));
        $item->transition(ContentState::PROCESSED, 'foobar');

        $this->uglify_js_listener->onPreWrite(new AssetEvent($item));

        self::assertSame(ContentState::PROCESSED, $item->getState()->current());
        self::assertContains('foobar', $item->getContent());
    }
}

<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\EventListener;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Bundler\Pipeline\ContentPipelineInterface;
use Hostnet\Component\Resolver\ConfigInterface;
use Hostnet\Component\Resolver\Event\AssetEvent;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\FileReader;
use Hostnet\Component\Resolver\FileSystem\StringReader;
use Hostnet\Component\Resolver\Import\Dependency;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @covers \Hostnet\Component\Resolver\EventListener\AngularHtmlListener
 */
class AngularHtmlListenerTest extends TestCase
{
    private $config;
    private $pipeline;

    /**
     * @var AngularHtmlListener
     */
    private $angular_html_listener;

    protected function setUp()
    {
        $this->config   = $this->prophesize(ConfigInterface::class);
        $this->pipeline = $this->prophesize(ContentPipelineInterface::class);

        $this->angular_html_listener = new AngularHtmlListener(
            $this->config->reveal(),
            $this->pipeline->reveal()
        );
    }

    public function testOnPostTranspile()
    {
        $item = new ContentItem(new File('app.component.ts'), 'app.component', new StringReader(''));
        $item->transition(ContentState::READY, file_get_contents(__DIR__ . '/fixtures/app.component.js'), 'js');

        $this->config->cwd()->willReturn(__DIR__ . '/fixtures');
        $this->config->getSourceRoot()->willReturn('');
        $this->config->getOutputFolder()->willReturn('dev');

        $html = new File('app.component.html');
        $less = new File('app.component.less');

        $this->pipeline->peek($html)->willReturn('html');
        $this->pipeline->peek($less)->willReturn('css');
        $this->pipeline
            ->push([new Dependency($html)], new File('dev/app.component.html'), Argument::type(FileReader::class))
            ->willReturn('<html>foobar</html>');
        $this->pipeline
            ->push([new Dependency($less)], new File('dev/app.component.css'), Argument::type(FileReader::class))
            ->willReturn('div {color: red;}');

        $this->angular_html_listener->onPostTranspile(new AssetEvent($item));

        self::assertSame(ContentState::READY, $item->getState()->current());
        self::assertStringEqualsFile(__DIR__ . '/fixtures/expected.component.js', $item->getContent());
    }

    public function testOnPostTranspileWithSource()
    {
        $item = new ContentItem(new File('fixtures/test/app.component.ts'), 'app.component', new StringReader(''));
        $item->transition(ContentState::READY, file_get_contents(__DIR__ . '/fixtures/test/app2.component.js'), 'js');

        $this->config->cwd()->willReturn(__DIR__);
        $this->config->getSourceRoot()->willReturn('fixtures');
        $this->config->getOutputFolder()->willReturn('dev');

        $html = new File('fixtures/test/app.component.html');
        $less = new File('fixtures/test/app.component.less');

        $this->pipeline->peek($html)->willReturn('html');
        $this->pipeline->peek($less)->willReturn('css');
        $this->pipeline
            ->push([new Dependency($html)], new File('dev/test/app.component.html'), Argument::type(FileReader::class))
            ->willReturn('<html>foobar</html>');
        $this->pipeline
            ->push([new Dependency($less)], new File('dev/test/app.component.css'), Argument::type(FileReader::class))
            ->willReturn('div {color: red;}');

        $this->angular_html_listener->onPostTranspile(new AssetEvent($item));

        self::assertSame(ContentState::READY, $item->getState()->current());
        self::assertStringEqualsFile(__DIR__ . '/fixtures/expected.component.js', $item->getContent());
    }

    public function testOnPostTranspileNotComponent()
    {
        $item = new ContentItem(new File('app.js'), 'app', new StringReader(''));
        $item->transition(ContentState::READY, 'foobar', 'js');

        $this->angular_html_listener->onPostTranspile(new AssetEvent($item));

        self::assertSame(ContentState::READY, $item->getState()->current());
        self::assertSame('foobar', $item->getContent());
    }
}

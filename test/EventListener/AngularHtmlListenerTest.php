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
use Hostnet\Component\Resolver\Import\ImportFinderInterface;
use Hostnet\Component\Resolver\Import\RootFile;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @covers \Hostnet\Component\Resolver\EventListener\AngularHtmlListener
 */
class AngularHtmlListenerTest extends TestCase
{
    private $config;
    private $pipeline;
    private $finder;

    /**
     * @var AngularHtmlListener
     */
    private $angular_html_listener;

    protected function setUp()
    {
        $this->config   = $this->prophesize(ConfigInterface::class);
        $this->pipeline = $this->prophesize(ContentPipelineInterface::class);
        $this->finder   = $this->prophesize(ImportFinderInterface::class);

        $this->angular_html_listener = new AngularHtmlListener(
            $this->config->reveal(),
            $this->pipeline->reveal(),
            $this->finder->reveal()
        );
    }

    public function testOnPostTranspile()
    {
        $item = new ContentItem(new File('app.component.ts'), 'app.component', new StringReader(''));
        $item->transition(ContentState::READY, file_get_contents(__DIR__ . '/fixtures/app.component.js'), 'js');

        $this->config->cwd()->willReturn(__DIR__ . '/fixtures');
        $this->config->getSourceRoot()->willReturn('');
        $this->config->getOutputFolder()->willReturn('dev');

        $less      = new File('app.component.less');
        $less_root = new RootFile($less);
        $this->finder->all($less)->willReturn($less_root);

        $html      = new File('app.component.html');
        $html_root = new RootFile($html);
        $this->finder->all($html)->willReturn($html_root);

        $this->pipeline->peek($html)->willReturn('html');
        $this->pipeline->peek($less)->willReturn('css');
        $this->pipeline
            ->push([$html_root], Argument::type(FileReader::class))
            ->willReturn('<html>foobar</html>');
        $this->pipeline
            ->push([$less_root], Argument::type(FileReader::class))
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

        $less      = new File('fixtures/test/app.component.less');
        $less_root = new RootFile($less);
        $this->finder->all($less)->willReturn($less_root);

        $html      = new File('fixtures/test/app.component.html');
        $html_root = new RootFile($html);
        $this->finder->all($html)->willReturn($html_root);

        $this->pipeline->peek($html)->willReturn('html');
        $this->pipeline->peek($less)->willReturn('css');
        $this->pipeline
            ->push([$html_root], Argument::type(FileReader::class))
            ->willReturn('<html>foobar</html>');
        $this->pipeline
            ->push([$less_root], Argument::type(FileReader::class))
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

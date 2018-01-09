<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler;

use Hostnet\Component\Resolver\Bundler\Pipeline\ContentPipelineInterface;
use Hostnet\Component\Resolver\Bundler\Runner\RunnerInterface;
use Hostnet\Component\Resolver\Bundler\Runner\RunnerType;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\Event\BundleEvent;
use Hostnet\Component\Resolver\Event\BundleEvents;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\ReaderInterface;
use Hostnet\Component\Resolver\FileSystem\WriterInterface;
use Hostnet\Component\Resolver\Import\Dependency;
use Hostnet\Component\Resolver\Import\ImportFinderInterface;
use Hostnet\Component\Resolver\Import\RootFile;
use Hostnet\Component\Resolver\Report\NullReporter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \Hostnet\Component\Resolver\Bundler\PipelineBundler
 */
class PipelineBundlerTest extends TestCase
{
    private $finder;
    private $pipeline;
    private $config;
    private $runner;

    /**
     * @var PipelineBundler
     */
    private $pipeline_bundler;

    protected function setUp()
    {
        $this->finder   = $this->prophesize(ImportFinderInterface::class);
        $this->pipeline = $this->prophesize(ContentPipelineInterface::class);
        $this->config   = $this->prophesize(ConfigInterface::class);
        $this->runner   = $this->prophesize(RunnerInterface::class);

        $this->pipeline_bundler = new PipelineBundler(
            $this->finder->reveal(),
            $this->pipeline->reveal(),
            $this->config->reveal(),
            $this->runner->reveal()
        );
    }

    public function testExecute()
    {
        $reader           = $this->prophesize(ReaderInterface::class);
        $writer           = $this->prophesize(WriterInterface::class);
        $event_dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $event_dispatcher->dispatch(BundleEvents::PRE_BUNDLE, new BundleEvent())->shouldBeCalled();
        $event_dispatcher->dispatch(BundleEvents::POST_BUNDLE, new BundleEvent())->shouldBeCalled();

        $this->config->getOutputFolder()->willReturn('dev1');
        $this->config->getSourceRoot()->willReturn('');
        $this->config->isDev()->willReturn(true);
        $this->config->getCacheDir()->willReturn(__DIR__ . '/dev1-cache');
        $this->config->getProjectRoot()->willReturn(__DIR__);
        $this->config->getEntryPoints()->willReturn(['foo.js']);
        $this->config->getAssetFiles()->willReturn(['bar.js']);
        $this->config->getExcludedFiles()->willReturn([]);
        $this->config->getEventDispatcher()->willReturn($event_dispatcher);
        $this->config->getReporter()->willReturn(new NullReporter());

        $entry_point1 = new RootFile(new File('foo.js'));
        $entry_point2 = new RootFile(new File('bar.js'));
        $entry_point3 = new RootFile(new File('asset.js'));

        $entry_point1->addChild(new Dependency(new File('asset.js'), false, true));
        $entry_point1->addChild(new Dependency(new File('asset.js'), false, true));

        $this->finder->all(Argument::that(function (File $file) {
            return $file->path === 'foo.js';
        }))->willReturn($entry_point1);
        $this->finder->all(Argument::that(function (File $file) {
            return $file->path === 'bar.js';
        }))->willReturn($entry_point2);
        $this->finder->all(Argument::that(function (File $file) {
            return $file->path === 'asset.js';
        }))->willReturn($entry_point3);

        $this->pipeline
            ->push([$entry_point1], $reader->reveal(), new File('dev1/foo.bundle.js'))
            ->willReturn('foo.js bundle');
        $this->pipeline
            ->push([], $reader->reveal(), new File('dev1/foo.vendor.js'))
            ->willReturn('foo.js vendor');
        $this->pipeline
            ->push([$entry_point2], $reader->reveal(), new File('dev1/bar.js'))
            ->willReturn('bar.js content');
        $this->pipeline
            ->push([$entry_point3], $reader->reveal(), new File('dev1/asset.js'))
            ->willReturn('asset.js content');
        $this->pipeline->peek(new File('bar.js'))->willReturn('js');
        $this->pipeline->peek(new File('asset.js'))->willReturn('js');

        $this->runner->execute(RunnerType::UGLIFY, Argument::that(function (ContentItem $item) {
            return false !== strpos($item->file->path, '/src/Resources/require.js');
        }))->willReturn('foobar uglified');

        $reader->read(Argument::that(function (File $file) {
            return false !== strpos($file->path, '/src/Resources/require.js');
        }))->willReturn('foobar');

        $writer->write(Argument::that(function (File $file) {
            return $file->path === 'dev1/require.js';
        }), 'foobar uglified')->shouldBeCalled();
        $writer->write(Argument::that(function (File $file) {
            return $file->path === 'dev1/foo.bundle.js';
        }), 'foo.js bundle')->shouldBeCalled();
        $writer->write(Argument::that(function (File $file) {
            return $file->path === 'dev1/foo.vendor.js';
        }), 'foo.js vendor')->shouldBeCalled();
        $writer->write(Argument::that(function (File $file) {
            return $file->path === 'dev1/bar.js';
        }), 'bar.js content')->shouldBeCalled();
        $writer->write(Argument::that(function (File $file) {
            return $file->path === 'dev1/asset.js';
        }), 'asset.js content')->shouldBeCalled();

        $this->pipeline_bundler->execute($reader->reveal(), $writer->reveal());
    }

    public function testExecuteNotChanged()
    {
        $reader           = $this->prophesize(ReaderInterface::class);
        $writer           = $this->prophesize(WriterInterface::class);
        $event_dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $event_dispatcher->dispatch(BundleEvents::PRE_BUNDLE, new BundleEvent())->shouldBeCalled();
        $event_dispatcher->dispatch(BundleEvents::POST_BUNDLE, new BundleEvent())->shouldBeCalled();

        $this->config->getOutputFolder()->willReturn('dev2');
        $this->config->getSourceRoot()->willReturn('');
        $this->config->isDev()->willReturn(true);
        $this->config->getCacheDir()->willReturn(__DIR__ . '/dev2-cache');
        $this->config->getProjectRoot()->willReturn(__DIR__);
        $this->config->getEntryPoints()->willReturn(['foobar.js']);
        $this->config->getAssetFiles()->willReturn([]);
        $this->config->getExcludedFiles()->willReturn([]);
        $this->config->getEventDispatcher()->willReturn($event_dispatcher);
        $this->config->getReporter()->willReturn(new NullReporter());

        $entry_point1 = new RootFile(new File('foobar.js'));

        $this->finder->all(Argument::that(function (File $file) {
            return $file->path === 'foobar.js';
        }))->willReturn($entry_point1);

        $this->pipeline
            ->push([$entry_point1], $reader->reveal(), new File('dev2/foobar.bundle.js'))
            ->willReturn('foobar.js bundle');
        $this->pipeline
            ->push([], $reader->reveal(), new File('dev2/foobar.vendor.js'))
            ->willReturn('foobar.js vendor');

        $this->runner->execute(RunnerType::UGLIFY, Argument::that(function (ContentItem $item) {
            return false !== strpos($item->file->path, '/src/Resources/require.js');
        }))->willReturn('uglified foobar');

        $reader->read(Argument::that(function (File $file) {
            return false !== strpos($file->path, '/src/Resources/require.js');
        }))->willReturn('foobar');

        $writer->write(Argument::that(function (File $file) {
            return $file->path === 'dev2/require.js';
        }), 'uglified foobar')->shouldBeCalled();

        $this->pipeline_bundler->execute($reader->reveal(), $writer->reveal());
    }

    public function testExecuteWithExcludes()
    {
        $reader           = $this->prophesize(ReaderInterface::class);
        $writer           = $this->prophesize(WriterInterface::class);
        $event_dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $event_dispatcher->dispatch(BundleEvents::PRE_BUNDLE, new BundleEvent())->shouldBeCalled();
        $event_dispatcher->dispatch(BundleEvents::POST_BUNDLE, new BundleEvent())->shouldBeCalled();

        $this->config->getOutputFolder()->willReturn('dev1');
        $this->config->getSourceRoot()->willReturn('');
        $this->config->isDev()->willReturn(true);
        $this->config->getCacheDir()->willReturn(__DIR__ . '/dev1-cache');
        $this->config->getProjectRoot()->willReturn(__DIR__);
        $this->config->getEntryPoints()->willReturn(['foo.js']);
        $this->config->getAssetFiles()->willReturn([]);
        $this->config->getExcludedFiles()->willReturn(['bar.js']);
        $this->config->getEventDispatcher()->willReturn($event_dispatcher);
        $this->config->getReporter()->willReturn(new NullReporter());

        $bar          = new RootFile(new File('bar.js'));
        $baz          = new RootFile(new File('baz.js'));
        $entry_point1 = new RootFile(new File('foo.js'));
        $entry_point1->addChild($bar);

        $bar->addChild($baz);

        $this->finder->all(Argument::that(function (File $file) {
            return $file->path === 'foo.js';
        }))->willReturn($entry_point1);

        $this->finder->all(Argument::that(function (File $file) {
            return $file->path === 'bar.js';
        }))->willReturn($bar);

        $this->pipeline
            ->push([$entry_point1], $reader->reveal(), new File('dev1/foo.bundle.js'))
            ->willReturn('foo.js bundle');
        $this->pipeline
            ->push([], $reader->reveal(), new File('dev1/foo.vendor.js'))
            ->willReturn('foo.js vendor');

        $this->runner->execute(RunnerType::UGLIFY, Argument::that(function (ContentItem $item) {
            return false !== strpos($item->file->path, '/src/Resources/require.js');
        }))->willReturn('foobar uglified');

        $reader->read(Argument::that(function (File $file) {
            return false !== strpos($file->path, '/src/Resources/require.js');
        }))->willReturn('foobar');

        $writer->write(Argument::that(function (File $file) {
            return $file->path === 'dev1/require.js';
        }), 'foobar uglified')->shouldBeCalled();
        $writer->write(Argument::that(function (File $file) {
            return $file->path === 'dev1/foo.bundle.js';
        }), 'foo.js bundle')->shouldBeCalled();
        $writer->write(Argument::that(function (File $file) {
            return $file->path === 'dev1/foo.vendor.js';
        }), 'foo.js vendor')->shouldBeCalled();

        $this->pipeline_bundler->execute($reader->reveal(), $writer->reveal());
    }
}

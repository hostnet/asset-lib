<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler\Pipeline;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Bundler\Processor\ContentProcessorInterface;
use Hostnet\Component\Resolver\Bundler\Processor\IdentityProcessor;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\FileReader;
use Hostnet\Component\Resolver\FileSystem\ReaderInterface;
use Hostnet\Component\Resolver\FileSystem\WriterInterface;
use Hostnet\Component\Resolver\Import\Dependency;
use Hostnet\Component\Resolver\Import\RootFile;
use Hostnet\Component\Resolver\Module;
use Hostnet\Component\Resolver\Report\NullReporter;
use Hostnet\Component\Resolver\Report\ReporterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \Hostnet\Component\Resolver\Bundler\Pipeline\ContentPipeline
 */
class ContentPipelineTest extends TestCase
{
    private $dispatcher;
    private $config;
    private $writer;

    /**
     * @var ContentPipelineInterface
     */
    private $content_pipeline;

    protected function setUp()
    {
        $this->dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->config     = $this->prophesize(ConfigInterface::class);
        $this->writer     = $this->prophesize(WriterInterface::class);

        $this->config->getProjectRoot()->willReturn(__DIR__);

        $this->content_pipeline = new ContentPipeline(
            $this->dispatcher->reveal(),
            $this->config->reveal(),
            $this->writer->reveal()
        );
    }

    public function testPeek()
    {
        $file = new File('bar.foo');

        $this->content_pipeline->addProcessor(new IdentityProcessor('foo'));

        self::assertEquals('foo', $this->content_pipeline->peek($file));
    }

    public function testPush()
    {
        $this->config->isDev()->willReturn(false);
        $this->config->getSourceRoot()->willReturn('fixtures');
        $this->config->getCacheDir()->willReturn(__DIR__ . '/cache/new');
        $this->config->getReporter()->willReturn(new NullReporter());

        $input_file  = new RootFile(new Module('fixtures/bar.foo', 'fixtures/bar.foo'));
        $target_file = new File('output.foo');
        $reader      = new FileReader(__DIR__);

        $input_file->addChild($d1 = new Dependency(new File('foo.foo'), true));
        $input_file->addChild($d2 = new Dependency(new Module('fixtures/foo/bar.foo', 'fixtures/foo/bar.foo')));
        $input_file->addChild($d3 = new Dependency(
            new Module('node_modules/fixtures/foo/abc.def', 'node_modules/fixtures/foo/abc.def')
        ));

        $def_processor = new class implements ContentProcessorInterface
        {
            public function supports(ContentState $state): bool
            {
                return $state->extension() == 'def';
            }

            public function peek(string $cwd, ContentState $state): void
            {
                $state->transition(ContentState::READY);
            }

            public function transpile(string $cwd, ContentItem $item): void
            {
                if ($item->module_name == 'node_modules/fixtures/foo/abc.def') {
                    $item->transition(ContentState::READY);
                }
            }
        };

        $this->content_pipeline->addProcessor(new IdentityProcessor('foo'));
        $this->content_pipeline->addProcessor($def_processor);

        self::assertEquals(
            "foobar\nfoobar\nbla\n",
            $this->content_pipeline->push([$input_file, $d1, $d2, $d3], $reader, $target_file)
        );
    }

    /**
     * This works because no output file is made
     */
    public function testPushDev()
    {
        $this->config->isDev()->willReturn(true);
        $this->config->getSourceRoot()->willReturn('fixtures');
        $this->config->getCacheDir()->willReturn(__DIR__ . '/cache/new');
        $this->config->getReporter()->willReturn(new NullReporter());

        $input_file  = new RootFile(new Module('fixtures/bar.foo', 'fixtures/bar.foo'));
        $target_file = new File('output.foo');
        $reader      = new FileReader(__DIR__);

        $input_file->addChild($d1 = new Dependency(new File('foo.foo'), true));
        $input_file->addChild($d2 = new Dependency(new Module('fixtures/foo/bar.foo', 'fixtures/foo/bar.foo')));

        $this->content_pipeline->addProcessor(new IdentityProcessor('foo'));

        $this->writer->write(Argument::type(File::class), Argument::type('string'))->shouldBeCalled();

        self::assertEquals(
            "foobar\nfoobar\n",
            $this->content_pipeline->push([$input_file, $d1, $d2], $reader, $target_file)
        );
    }

    public function testPushDevAlreadyUpToDate()
    {
        $this->config->isDev()->willReturn(true);
        $this->config->getSourceRoot()->willReturn('fixtures');
        $this->config->getCacheDir()->willReturn(__DIR__ . '/cache/new');
        $this->config->getReporter()->willReturn(new NullReporter());

        $input_file  = new RootFile(new Module('fixtures/bar.foo', 'fixtures/bar.foo'));
        $target_file = new File('fixtures/output.foo');
        $reader      = new FileReader(__DIR__);

        $input_file->addChild($d1 = new Dependency(new File('fixtures/foo.foo'), true));
        $input_file->addChild($d2 = new Dependency(new Module('fixtures/foo/bar.foo', 'fixtures/foo/bar.foo')));

        $this->content_pipeline->addProcessor(new IdentityProcessor('foo'));

        self::assertEquals(
            "foobar\nfoobar\n",
            $this->content_pipeline->push([$input_file, $d1, $d2], $reader, $target_file)
        );
    }

    public function testPushDevInlineChanged()
    {
        $reporter = new class implements ReporterInterface
        {
            public $files;

            public function reportOutputFile(File $file): void
            {
            }
            public function reportFileDependencies(File $file, array $dependencies): void
            {
            }
            public function reportFileState(File $file, string $state): void
            {
                $this->files[$file->path] = $state;
            }
            public function reportFileContent(File $file, string $content): void
            {
            }
        };

        $this->config->isDev()->willReturn(true);
        $this->config->getSourceRoot()->willReturn('fixtures');
        $this->config->getCacheDir()->willReturn(__DIR__ . '/cache/new');
        $this->config->getReporter()->willReturn($reporter);

        $input_file  = new RootFile(new Module('fixtures/bar.foo', 'fixtures/bar.foo'));
        $target_file = new File('fixtures/output.foo');
        $reader      = new FileReader(__DIR__);

        $file = tempnam(__DIR__, 'asset');

        try {
            $input_file->addChild($dep = new Dependency(new File($file), true));

            $this->content_pipeline->addProcessor(new IdentityProcessor('foo'));
            $output = $this->content_pipeline->push([$input_file, $dep], $reader, $target_file);

            self::assertEquals("foobar\n", $output);
            self::assertEquals([
                'fixtures/bar.foo' => ReporterInterface::STATE_BUILT,
                $file              => ReporterInterface::STATE_INLINE,
            ], $reporter->files);
        } finally {
            unlink($file);
        }
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Failed to compile resource "input.js".
     */
    public function testPushNoStateChange()
    {
        $this->config->isDev()->willReturn(false);
        $this->config->getSourceRoot()->willReturn('fixtures');
        $this->config->getCacheDir()->willReturn(__DIR__ . '/cache/new');
        $this->config->getReporter()->willReturn(new NullReporter());

        $input_file  = new RootFile(new Module('fixtures/input.js', 'fixtures/input.js'));
        $target_file = new File('output.foo');
        $reader      = new FileReader(__DIR__);

        $this->content_pipeline->addProcessor(new IdentityProcessor('foo'));

        self::assertEquals("foobar\nfoobar\n", $this->content_pipeline->push([$input_file], $reader, $target_file));
    }

    public function testPushWithTrailingSlashInSourceRoot()
    {
        $this->config->isDev()->willReturn(true);
        $this->config->getSourceRoot()->willReturn('fixtures/'); // Note this trailing slash
        $this->config->getCacheDir()->willReturn(__DIR__ . '/cache/new');
        $this->config->getReporter()->willReturn(new NullReporter());

        $file       = new File('fixtures/bla.foo');
        $dependency = new Dependency($file);
        $reader     = $this->prophesize(ReaderInterface::class);
        $reader->read($file)->willReturn('waddup');

        $this->content_pipeline->addProcessor(new IdentityProcessor('foo'));
        self::assertSame('waddup', $this->content_pipeline->push([$dependency], $reader->reveal()));
    }
}

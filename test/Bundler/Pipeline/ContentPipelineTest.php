<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler\Pipeline;

use Hostnet\Component\Resolver\Bundler\Processor\IdentityProcessor;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\FileReader;
use Hostnet\Component\Resolver\FileSystem\WriterInterface;
use Hostnet\Component\Resolver\Import\Dependency;
use Hostnet\Component\Resolver\Import\RootFile;
use Hostnet\Component\Resolver\Module;
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
            new NullLogger(),
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

        $input_file  = new RootFile(new Module('fixtures/bar.foo', 'fixtures/bar.foo'));
        $target_file = new File('output.foo');
        $reader      = new FileReader(__DIR__);

        $input_file->addChild($d1 = new Dependency(new File('foo.foo'), true));
        $input_file->addChild($d2 = new Dependency(new Module('fixtures/foo/bar.foo', 'fixtures/foo/bar.foo')));

        $this->content_pipeline->addProcessor(new IdentityProcessor('foo'));

        self::assertEquals(
            "foobar\nfoobar\n",
            $this->content_pipeline->push([$input_file, $d1, $d2], $reader, $target_file)
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

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Failed to compile resource "input.js".
     */
    public function testPushNoStateChange()
    {
        $this->config->isDev()->willReturn(false);
        $this->config->getSourceRoot()->willReturn('fixtures');
        $this->config->getCacheDir()->willReturn(__DIR__ . '/cache/new');

        $input_file  = new RootFile(new Module('fixtures/input.js', 'fixtures/input.js'));
        $target_file = new File('output.foo');
        $reader      = new FileReader(__DIR__);

        $this->content_pipeline->addProcessor(new IdentityProcessor('foo'));

        self::assertEquals("foobar\nfoobar\n", $this->content_pipeline->push([$input_file], $reader, $target_file));
    }
}

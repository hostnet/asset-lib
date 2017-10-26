<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Bundler\Pipeline\MutableContentPipelineInterface;
use Hostnet\Component\Resolver\Bundler\Processor\ContentProcessorInterface;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\Import\ImportCollectorInterface;
use Hostnet\Component\Resolver\Import\MutableImportFinderInterface;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

/**
 * @covers \Hostnet\Component\Resolver\Plugin\PluginApi
 */
class PluginApiTest extends TestCase
{
    private $pipeline;
    private $finder;
    private $config;
    private $cache;

    /**
     * @var PluginApi
     */
    private $plugin_api;

    protected function setUp()
    {
        $this->pipeline = $this->prophesize(MutableContentPipelineInterface::class);
        $this->finder   = $this->prophesize(MutableImportFinderInterface::class);
        $this->config   = $this->prophesize(ConfigInterface::class);
        $this->cache    = $this->prophesize(CacheInterface::class);

        $this->plugin_api = new PluginApi(
            $this->pipeline->reveal(),
            $this->finder->reveal(),
            $this->config->reveal(),
            $this->cache->reveal()
        );
    }

    public function testGetNodeJsExecutable()
    {
        $executable = new Executable('node', 'node_modules');
        $this->config->getNodeJsExecutable()->willReturn($executable);
        self::assertSame($executable, $this->plugin_api->getNodeJsExecutable());
    }

    public function testAddProcessor()
    {
        $processor = $this->prophesize(ContentProcessorInterface::class)->reveal();
        $this->pipeline->addProcessor($processor)->shouldBeCalled();
        $this->plugin_api->addProcessor($processor);
    }

    public function testAddCollector()
    {
        $collector = $this->prophesize(ImportCollectorInterface::class)->reveal();
        $this->finder->addCollector($collector)->shouldBeCalled();
        $this->plugin_api->addCollector($collector);
    }

    public function testGetters()
    {
        self::assertSame($this->pipeline->reveal(), $this->plugin_api->getPipeline());
        self::assertSame($this->finder->reveal(), $this->plugin_api->getFinder());
        self::assertSame($this->config->reveal(), $this->plugin_api->getConfig());
        self::assertSame($this->cache->reveal(), $this->plugin_api->getCache());
    }
}

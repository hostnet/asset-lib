<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Builder\AbstractBuildStep;
use Hostnet\Component\Resolver\Builder\AbstractWriter;
use Hostnet\Component\Resolver\Builder\BuildPlan;
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
    private $finder;
    private $config;
    private $cache;
    private $build_plan;

    /**
     * @var PluginApi
     */
    private $plugin_api;

    protected function setUp()
    {
        $this->finder     = $this->prophesize(MutableImportFinderInterface::class);
        $this->config     = $this->prophesize(ConfigInterface::class);
        $this->cache      = $this->prophesize(CacheInterface::class);
        $this->build_plan = $this->prophesize(BuildPlan::class);

        $this->plugin_api = new PluginApi(
            $this->finder->reveal(),
            $this->config->reveal(),
            $this->cache->reveal(),
            $this->build_plan->reveal()
        );
    }

    public function testGetNodeJsExecutable()
    {
        $executable = new Executable('node', 'node_modules');
        $this->config->getNodeJsExecutable()->willReturn($executable);
        self::assertSame($executable, $this->plugin_api->getNodeJsExecutable());
    }

    public function testAddBuildStep()
    {
        $build_step = $this->prophesize(AbstractBuildStep::class)->reveal();
        $this->build_plan->registerStep($build_step)->shouldBeCalled();

        $this->plugin_api->addBuildStep($build_step);
    }

    public function testAddWriter()
    {
        $writer = $this->prophesize(AbstractWriter::class)->reveal();
        $this->build_plan->registerWriter($writer)->shouldBeCalled();

        $this->plugin_api->addWriter($writer);
    }

    public function testAddCollector()
    {
        $collector = $this->prophesize(ImportCollectorInterface::class)->reveal();
        $this->finder->addCollector($collector)->shouldBeCalled();
        $this->plugin_api->addCollector($collector);
    }

    public function testGetters()
    {
        self::assertSame($this->config->reveal(), $this->plugin_api->getConfig());
        self::assertSame($this->cache->reveal(), $this->plugin_api->getCache());
    }
}

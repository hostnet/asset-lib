<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Builder\AbstractBuildStep;
use Hostnet\Component\Resolver\Builder\AbstractWriter;
use Hostnet\Component\Resolver\Builder\BuildConfig;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\Import\ImportCollectorInterface;
use Hostnet\Component\Resolver\Import\MutableImportFinderInterface;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\SimpleCache\CacheInterface;

/**
 * @covers \Hostnet\Component\Resolver\Plugin\PluginApi
 */
class PluginApiTest extends TestCase
{
    use ProphecyTrait;

    private $finder;
    private $config;
    private $cache;
    private $build_config;

    /**
     * @var PluginApi
     */
    private $plugin_api;

    protected function setUp(): void
    {
        $this->finder       = $this->prophesize(MutableImportFinderInterface::class);
        $this->config       = $this->prophesize(ConfigInterface::class);
        $this->cache        = $this->prophesize(CacheInterface::class);
        $this->build_config = $this->prophesize(BuildConfig::class);

        $this->plugin_api = new PluginApi(
            $this->finder->reveal(),
            $this->config->reveal(),
            $this->cache->reveal(),
            $this->build_config->reveal()
        );
    }

    public function testGetNodeJsExecutable(): void
    {
        $executable = new Executable('node', 'node_modules');
        $this->config->getNodeJsExecutable()->willReturn($executable);
        self::assertSame($executable, $this->plugin_api->getNodeJsExecutable());
    }

    public function testAddBuildStep(): void
    {
        $build_step = $this->prophesize(AbstractBuildStep::class)->reveal();
        $this->build_config->registerStep($build_step)->shouldBeCalled();

        $this->plugin_api->addBuildStep($build_step);
    }

    public function testAddWriter(): void
    {
        $writer = $this->prophesize(AbstractWriter::class)->reveal();
        $this->build_config->registerWriter($writer)->shouldBeCalled();

        $this->plugin_api->addWriter($writer);
    }

    public function testAddCollector(): void
    {
        $collector = $this->prophesize(ImportCollectorInterface::class)->reveal();
        $this->finder->addCollector($collector)->shouldBeCalled();
        $this->plugin_api->addCollector($collector);
    }

    public function testGetters(): void
    {
        self::assertSame($this->config->reveal(), $this->plugin_api->getConfig());
        self::assertSame($this->cache->reveal(), $this->plugin_api->getCache());
    }
}

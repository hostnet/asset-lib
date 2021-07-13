<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Builder\Step\TypescriptBuildStep;
use Hostnet\Component\Resolver\Cache\CachedImportCollector;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\Import\BuiltIn\TsImportCollector;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\SimpleCache\CacheInterface;

/**
 * @covers \Hostnet\Component\Resolver\Plugin\TsPlugin
 */
class TsPluginTest extends TestCase
{
    use ProphecyTrait;

    public function testActivate(): void
    {
        $cache  = $this->prophesize(CacheInterface::class);
        $config = $this->prophesize(ConfigInterface::class);
        $config->isDev()->willReturn(true);

        $plugin_api = $this->prophesize(PluginApi::class);
        $plugin_api->getConfig()->willReturn($config);
        $plugin_api->getCache()->willReturn($cache);
        $plugin_api->addCollector(Argument::type(CachedImportCollector::class))->shouldBeCalled();
        $plugin_api->addBuildStep(Argument::type(TypescriptBuildStep::class))->shouldBeCalled();

        $ts_plugin = new TsPlugin();
        $ts_plugin->activate($plugin_api->reveal());
    }

    public function testActivateProd(): void
    {
        $cache  = $this->prophesize(CacheInterface::class);
        $config = $this->prophesize(ConfigInterface::class);
        $config->isDev()->willReturn(false);

        $plugin_api = $this->prophesize(PluginApi::class);
        $plugin_api->getConfig()->willReturn($config);
        $plugin_api->getCache()->willReturn($cache);
        $plugin_api->addCollector(Argument::type(TsImportCollector::class))->shouldBeCalled();
        $plugin_api->addBuildStep(Argument::type(TypescriptBuildStep::class))->shouldBeCalled();

        $ts_plugin = new TsPlugin();
        $ts_plugin->activate($plugin_api->reveal());
    }
}

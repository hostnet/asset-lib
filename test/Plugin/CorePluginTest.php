<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Builder\Step\CssBuildStep;
use Hostnet\Component\Resolver\Builder\Step\JsBuildStep;
use Hostnet\Component\Resolver\Builder\Step\ModuleBuildStep;
use Hostnet\Component\Resolver\Builder\Writer\GenericFileWriter;
use Hostnet\Component\Resolver\Cache\CachedImportCollector;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\Import\BuiltIn\JsImportCollector;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\SimpleCache\CacheInterface;

/**
 * @covers \Hostnet\Component\Resolver\Plugin\CorePlugin
 */
class CorePluginTest extends TestCase
{
    public function testActivate()
    {
        $cache  = $this->prophesize(CacheInterface::class);
        $config = $this->prophesize(ConfigInterface::class);
        $config->isDev()->willReturn(true);

        $plugin_api = $this->prophesize(PluginApi::class);
        $plugin_api->getConfig()->willReturn($config);
        $plugin_api->getCache()->willReturn($cache);
        $plugin_api->addCollector(Argument::type(CachedImportCollector::class))->shouldBeCalled();
        $plugin_api->addBuildStep(Argument::type(CssBuildStep::class))->shouldBeCalled();
        $plugin_api->addBuildStep(Argument::type(JsBuildStep::class))->shouldBeCalled();
        $plugin_api->addBuildStep(Argument::type(ModuleBuildStep::class))->shouldBeCalled();
        $plugin_api->addWriter(Argument::type(GenericFileWriter::class))->shouldBeCalled();

        $core_plugin = new CorePlugin();
        $core_plugin->activate($plugin_api->reveal());
    }

    public function testActivateProd()
    {
        $cache  = $this->prophesize(CacheInterface::class);
        $config = $this->prophesize(ConfigInterface::class);
        $config->isDev()->willReturn(false);

        $plugin_api = $this->prophesize(PluginApi::class);
        $plugin_api->getConfig()->willReturn($config);
        $plugin_api->getCache()->willReturn($cache);
        $plugin_api->addCollector(Argument::type(JsImportCollector::class))->shouldBeCalled();
        $plugin_api->addBuildStep(Argument::type(CssBuildStep::class))->shouldBeCalled();
        $plugin_api->addBuildStep(Argument::type(JsBuildStep::class))->shouldBeCalled();
        $plugin_api->addBuildStep(Argument::type(ModuleBuildStep::class))->shouldBeCalled();
        $plugin_api->addWriter(Argument::type(GenericFileWriter::class))->shouldBeCalled();

        $core_plugin = new CorePlugin();
        $core_plugin->activate($plugin_api->reveal());
    }
}

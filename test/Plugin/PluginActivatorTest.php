<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Config\ConfigInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Plugin\PluginActivator
 */
class PluginActivatorTest extends TestCase
{
    public function testEnsurePluginsAreActivated()
    {
        $plugin = $this->prophesize(PluginInterface::class);

        $plugins = [$plugin];
        $config  = $this->prophesize(ConfigInterface::class);
        $config->getPlugins()->willReturn($plugins);

        $plugin_api = $this->prophesize(PluginApi::class);
        $plugin_api->getConfig()->willReturn($config);

        $plugin->activate($plugin_api)->shouldBeCalledTimes(1);

        $plugin_activator = new PluginActivator($plugin_api->reveal());
        $plugin_activator->ensurePluginsAreActivated();
        $plugin_activator->ensurePluginsAreActivated();
    }
}

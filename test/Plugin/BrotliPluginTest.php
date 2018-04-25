<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Bundler\Runner\RunnerInterface;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\Event\FileEvents;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @covers \Hostnet\Component\Resolver\Plugin\BrotliPlugin
 */
class BrotliPluginTest extends TestCase
{
    public function testActivate()
    {
        $brotli_plugin = new BrotliPlugin();

        $event_dispatcher = new EventDispatcher();

        $config = $this->prophesize(ConfigInterface::class);
        $config->getEventDispatcher()->willReturn($event_dispatcher);
        $config->getProjectRoot()->willReturn(__DIR__);

        $runner = $this->prophesize(RunnerInterface::class);

        $plugin_api = $this->prophesize(PluginApi::class);
        $plugin_api->getRunner()->wilLReturn($runner->reveal());
        $plugin_api->getConfig()->willReturn($config->reveal());

        self::assertFalse($event_dispatcher->hasListeners(FileEvents::POST_WRITE));
        $brotli_plugin->activate($plugin_api->reveal());
        self::assertTrue($event_dispatcher->hasListeners(FileEvents::POST_WRITE));
    }
}

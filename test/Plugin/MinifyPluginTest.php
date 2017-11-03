<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Bundler\Pipeline\MutableContentPipelineInterface;
use Hostnet\Component\Resolver\Bundler\Runner\RunnerInterface;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\Event\AssetEvents;
use Hostnet\Component\Resolver\Import\MutableImportFinderInterface;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @covers \Hostnet\Component\Resolver\Plugin\MinifyPlugin
 */
class MinifyPluginTest extends TestCase
{
    public function testActivate()
    {
        $event_dispatcher = new EventDispatcher();

        $pipeline = $this->prophesize(MutableContentPipelineInterface::class);
        $cache    = $this->prophesize(CacheInterface::class);
        $config   = $this->prophesize(ConfigInterface::class);
        $config->getNodeJsExecutable()->willReturn(new Executable('a', 'b'));
        $config->getEventDispatcher()->willReturn($event_dispatcher);
        $config->getRunner()->willReturn($this->prophesize(RunnerInterface::class));
        $finder     = $this->prophesize(MutableImportFinderInterface::class);
        $plugin_api = new PluginApi($pipeline->reveal(), $finder->reveal(), $config->reveal(), $cache->reveal());

        $minify_plugin = new MinifyPlugin();
        $minify_plugin->activate($plugin_api);
        self::assertCount(2, $event_dispatcher->getListeners(AssetEvents::READY));
    }
}

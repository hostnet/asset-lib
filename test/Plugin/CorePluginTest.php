<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Bundler\Pipeline\MutableContentPipelineInterface;
use Hostnet\Component\Resolver\Bundler\Processor\IdentityProcessor;
use Hostnet\Component\Resolver\Bundler\Processor\JsonProcessor;
use Hostnet\Component\Resolver\Bundler\Processor\ModuleProcessor;
use Hostnet\Component\Resolver\Cache\CachedImportCollector;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\Config\UnixSocketType;
use Hostnet\Component\Resolver\Event\AssetEvents;
use Hostnet\Component\Resolver\Import\BuiltIn\JsImportCollector;
use Hostnet\Component\Resolver\Import\MutableImportFinderInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @covers \Hostnet\Component\Resolver\Plugin\CorePlugin
 */
class CorePluginTest extends TestCase
{
    /**
     * @dataProvider activateProvider
     */
    public function testActivate(string $expected_collector_class, bool $is_dev)
    {
        $event_dispatcher = new EventDispatcher();

        $pipeline = $this->prophesize(MutableContentPipelineInterface::class);
        $pipeline->addProcessor(Argument::type(IdentityProcessor::class))->shouldBeCalled();
        $pipeline->addProcessor(Argument::type(ModuleProcessor::class))->shouldBeCalled();
        $pipeline->addProcessor(Argument::type(JsonProcessor::class))->shouldBeCalled();

        $cache  = $this->prophesize(CacheInterface::class);
        $config = $this->prophesize(ConfigInterface::class);
        $config->isDev()->willReturn($is_dev);
        $config->getEventDispatcher()->willReturn($event_dispatcher);
        $config->getSocketType()->willReturn(UnixSocketType::DISABLED);
        $finder = $this->prophesize(MutableImportFinderInterface::class);
        $finder->addCollector(Argument::type($expected_collector_class))->shouldBeCalled();
        $plugin_api     = new PluginApi($pipeline->reveal(), $finder->reveal(), $config->reveal(), $cache->reveal());
        $angular_plugin = new CorePlugin();
        $angular_plugin->activate($plugin_api);
        self::assertCount(0, $event_dispatcher->getListeners(AssetEvents::POST_PROCESS));
    }

    public function activateProvider(): array
    {
        return [
            [JsImportCollector::class, false],
            [CachedImportCollector::class, true],
        ];
    }
}

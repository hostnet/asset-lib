<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Bundler\Processor\IdentityProcessor;
use Hostnet\Component\Resolver\Bundler\Processor\JsonProcessor;
use Hostnet\Component\Resolver\Bundler\Processor\ModuleProcessor;
use Hostnet\Component\Resolver\Cache\CachedImportCollector;
use Hostnet\Component\Resolver\Import\BuiltIn\JsImportCollector;
use Hostnet\Component\Resolver\Import\Nodejs\FileResolver;

/**
 * Basic support for the JavaScript/css/html languages.
 *
 * Don't really see a use-case for the asset-lib without this plugin.
 *
 * Hence it's enabled by default, see PluginActivator.
 */
final class CorePlugin implements PluginInterface
{
    public function activate(PluginApi $plugin_api): void
    {
        $config = $plugin_api->getConfig();
        $cache  = $plugin_api->getCache();

        $js_collector = new JsImportCollector(
            new FileResolver($config, ['.js', '.json', '.node'])
        );

        if ($config->isDev()) {
            $js_collector = new CachedImportCollector($js_collector, $cache);
        }

        $plugin_api->addCollector($js_collector);

        $plugin_api->addProcessor(new IdentityProcessor('js', ContentState::PROCESSED));
        $plugin_api->addProcessor(new ModuleProcessor());
        $plugin_api->addProcessor(new JsonProcessor());

        $plugin_api->addProcessor(new IdentityProcessor('css'));
        $plugin_api->addProcessor(new IdentityProcessor('html'));
    }
}

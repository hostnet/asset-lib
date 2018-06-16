<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Builder\Step\CssBuildStep;
use Hostnet\Component\Resolver\Builder\Step\JsBuildStep;
use Hostnet\Component\Resolver\Builder\Step\LessBuildStep;
use Hostnet\Component\Resolver\Builder\Step\ModuleBuildStep;
use Hostnet\Component\Resolver\Builder\Step\TypescriptBuildStep;
use Hostnet\Component\Resolver\Builder\Writer\GenericFileWriter;
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

        $plugin_api->addBuildStep(new ModuleBuildStep());
        $plugin_api->addBuildStep(new JsBuildStep());
        $plugin_api->addBuildStep(new CssBuildStep());

        $plugin_api->addWriter(new GenericFileWriter());
    }
}

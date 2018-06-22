<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Builder\Step\TypescriptBuildStep;
use Hostnet\Component\Resolver\Cache\CachedImportCollector;
use Hostnet\Component\Resolver\Import\BuiltIn\JsImportCollector;
use Hostnet\Component\Resolver\Import\BuiltIn\TsImportCollector;
use Hostnet\Component\Resolver\Import\Nodejs\FileResolver;

/**
 * Plugin to compile typescript files.
 */
final class TsPlugin implements PluginInterface
{
    public function activate(PluginApi $plugin_api): void
    {
        $plugin_api->addBuildStep(new TypescriptBuildStep());
        $ts_collector = new TsImportCollector(
            new JsImportCollector(
                new FileResolver($plugin_api->getConfig(), ['.ts', '.js', '.json', '.node'])
            ),
            new FileResolver($plugin_api->getConfig(), ['.ts', '.js', '.json', '.node'])
        );
        if ($plugin_api->getConfig()->isDev()) {
            $ts_collector = new CachedImportCollector($ts_collector, $plugin_api->getCache());
        }
        $plugin_api->addCollector($ts_collector);
    }
}

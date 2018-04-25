<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver;

use Hostnet\Component\Resolver\Bundler\Pipeline\ContentPipeline;
use Hostnet\Component\Resolver\Bundler\PipelineBundler;
use Hostnet\Component\Resolver\Cache\Cache;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\FileSystem\FileReader;
use Hostnet\Component\Resolver\FileSystem\FileWriter;
use Hostnet\Component\Resolver\Import\ImportFinder;
use Hostnet\Component\Resolver\Plugin\PluginActivator;
use Hostnet\Component\Resolver\Plugin\PluginApi;

/**
 * Simple facade that registered JS, CSS, TS and LESS compilation and runs it
 * using the 'entry-points.json' file.
 */
final class Packer
{
    public static function pack(ConfigInterface $config): void
    {
        $cache = new Cache($config->getCacheDir() . '/dependencies');
        $cache->load();

        $dispatcher = $config->getEventDispatcher();
        $runner     = $config->getRunner();

        $finder = new ImportFinder($config->getProjectRoot());

        $writer   = new FileWriter($dispatcher, $config->getProjectRoot());
        $pipeline = new ContentPipeline($dispatcher, $config, $writer);

        $plugin_api = new PluginApi($pipeline, $finder, $config, $cache);
        (new PluginActivator($plugin_api))->ensurePluginsAreActivated();

        $bundler = new PipelineBundler(
            $finder,
            $pipeline,
            $config,
            $runner
        );

        $bundler->execute(new FileReader($config->getProjectRoot()), $writer);

        if (!$config->isDev()) {
            return;
        }

        $cache->save();
    }
}

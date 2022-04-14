<?php
/**
 * @copyright 2017-2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver;

use Hostnet\Component\Resolver\Builder\BuildConfig;
use Hostnet\Component\Resolver\Builder\Bundler;
use Hostnet\Component\Resolver\Cache\Cache;
use Hostnet\Component\Resolver\Config\ConfigInterface;
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

        $finder = new ImportFinder($config->getProjectRoot());

        $build_config = new BuildConfig($config);
        $plugin_api   = new PluginApi($finder, $config, $cache, $build_config);
        (new PluginActivator($plugin_api))->ensurePluginsAreActivated();

        $bundler = new Bundler($finder, $config);
        $bundler->bundle($build_config);

        if (!$config->isDev()) {
            return;
        }

        $cache->save();
    }
}

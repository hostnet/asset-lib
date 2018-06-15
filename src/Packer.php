<?php
/**
 * @copyright 2017-2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver;

use Hostnet\Component\Resolver\Builder\BuildFiles;
use Hostnet\Component\Resolver\Builder\BuildPlan;
use Hostnet\Component\Resolver\Builder\ExtensionMap;
use Hostnet\Component\Resolver\Cache\Cache;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\Import\ImportFinder;
use Hostnet\Component\Resolver\Plugin\PluginActivator;
use Hostnet\Component\Resolver\Plugin\PluginApi;
use Symfony\Component\Process\Process;

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

        $plugin_api = new PluginApi($finder, $config, $cache);
        (new PluginActivator($plugin_api))->ensurePluginsAreActivated();

        if ($config->isDev()) {
            $cache->save();
        }
    }
}

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
        $build_plan = new BuildPlan($config);

        $plugin_api = new PluginApi($finder, $config, $cache, $build_plan);
        (new PluginActivator($plugin_api))->ensurePluginsAreActivated();

        $config_file = $config->getCacheDir() . '/build_config.json';
        $new_build_config = false;

        if (!file_exists($config_file) || !$build_plan->isUpToDateWith($json_data = json_decode(file_get_contents($config_file), true))) {
            echo "Compiling build config.\n";

            $build_plan->compile();
            file_put_contents($config_file, json_encode($build_plan, JSON_PRETTY_PRINT));

            $new_build_config = true;
            $extension_map = $build_plan->getExtensionMap();
        } else {
            $extension_map = new ExtensionMap($json_data['mapping']);

            echo "Build config already up to date.\n";
        }

        $build_files = new BuildFiles($finder, $extension_map, $config);
        $build_files->compile($new_build_config);

        if ($config->isDev()) {
            $cache->save();
        }

        file_put_contents($config->getCacheDir() . '/build_files.json', json_encode($build_files, JSON_PRETTY_PRINT));
        if (!$build_files->hasFiles()) {
            echo "Already up to date!\n";
            return;
        }

        $cmd = sprintf(
            "%s %s --debug --stdin %s",
            escapeshellarg($config->getNodeJsExecutable()->getBinary()),
            escapeshellarg(__DIR__ . '/Builder/js/build.js'),
            escapeshellarg($config_file)
        );

        $process = new Process($cmd, null, ['NODE_PATH' => $config->getNodeJsExecutable()->getNodeModulesLocation()], json_encode($build_files));
        $process->inheritEnvironmentVariables();

        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(
                'Cannot compile due to compiler error. Output: ' . $process->getOutput() . $process->getErrorOutput()
            );
        }

        echo $process->getOutput(), $process->getErrorOutput();
    }
}

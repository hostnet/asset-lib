<?php

namespace Hostnet\Component\Resolver\Builder;


use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\Import\ImportFinderInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class Bundler
{
    /**
     * @var ImportFinderInterface
     */
    private $finder;
    /**
     * @var ConfigInterface
     */
    private $config;
    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(ImportFinderInterface $finder, ConfigInterface $config, Filesystem $filesystem)
    {
        $this->finder     = $finder;
        $this->config     = $config;
        $this->filesystem = $filesystem;
    }

    public function bundle(BuildPlan $build_plan): void
    {
        $config_file = $this->config->getCacheDir() . '/build_config.json';
        $new_build_config = false;

        if (!file_exists($config_file) || !$build_plan->isUpToDateWith($json_data = json_decode(file_get_contents($config_file), true))) {
            echo "Compiling build config.\n";

            $build_plan->compile();
            $this->filesystem->dumpFile($config_file, json_encode($build_plan, JSON_PRETTY_PRINT));

            $new_build_config = true;
            $extension_map = $build_plan->getExtensionMap();
        } else {
            $extension_map = new ExtensionMap($json_data['mapping']);

            echo "Build config already up to date.\n";
        }

        $build_files = new BuildFiles($this->finder, $extension_map, $this->config);
        $build_files->compile($new_build_config);

        $this->filesystem->dumpFile($this->config->getCacheDir() . '/build_files.json', json_encode($build_files, JSON_PRETTY_PRINT));

        if (!$build_files->hasFiles()) {
            echo "Already up to date!\n";
            return;
        }

        $cmd = sprintf(
            "%s %s --debug --stdin %s",
            escapeshellarg($this->config->getNodeJsExecutable()->getBinary()),
            escapeshellarg(__DIR__ . '/js/build.js'),
            escapeshellarg($config_file)
        );

        $process = new Process($cmd, null, ['NODE_PATH' => $this->config->getNodeJsExecutable()->getNodeModulesLocation()], json_encode($build_files));
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

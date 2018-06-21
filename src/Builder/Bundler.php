<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\Import\ImportFinderInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final class Bundler implements BundlerInterface
{
    private $finder;
    private $config;
    private $build_script;

    public function __construct(
        ImportFinderInterface $finder,
        ConfigInterface $config,
        string $build_script = __DIR__ . '/js/build.js'
    ) {
        $this->finder       = $finder;
        $this->config       = $config;
        $this->build_script = $build_script;
    }

    public function bundle(BuildConfig $build_config): void
    {
        $filesystem       = new Filesystem();
        $config_file      = $this->config->getCacheDir() . '/build_config.json';
        $new_build_config = false;

        if (!file_exists($config_file)
            || !$build_config->isUpToDateWith($json_data = json_decode(file_get_contents($config_file), true))
        ) {
            $build_config->compile();
            $filesystem->dumpFile($config_file, json_encode($build_config, JSON_PRETTY_PRINT));

            $new_build_config = true;
            $extension_map = $build_config->getExtensionMap();
        } else {
            $extension_map = new ExtensionMap($json_data['mapping']);
        }

        $build_files = new BuildFiles($this->finder, $extension_map, $this->config);
        $build_files->compile($new_build_config);

        if (!$build_files->hasFiles()) {
            return;
        }

        $cmd = sprintf(
            '%s %s --debug --log-json --stdin %s',
            escapeshellarg($this->config->getNodeJsExecutable()->getBinary()),
            escapeshellarg($this->build_script),
            escapeshellarg($config_file)
        );

        $process = new Process($cmd, $this->config->getProjectRoot(), [
            'NODE_PATH' => $this->config->getNodeJsExecutable()->getNodeModulesLocation(),
        ], json_encode($build_files));
        $process->inheritEnvironmentVariables();

        $reader = new OutputReader($this->config->getReporter());

        $process->run(function ($type, $buffer) use ($reader) {
            if (Process::OUT !== $type) {
                return;
            }

            $reader->append($buffer);
        });

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(
                'Cannot compile due to compiler error. Output: ' . $process->getOutput() . $process->getErrorOutput()
            );
        }
    }
}

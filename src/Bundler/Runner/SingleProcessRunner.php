<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler\Runner;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\TranspileException;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\File;
use Symfony\Component\Process\Process;

/**
 * Executes the given javascript file with node.
 *
 * Gives the file name as the first argument.
 *
 * Puts the content of the file on stdin, expects response on stdout.
 */
class SingleProcessRunner implements RunnerInterface
{
    private $config;
    private $files;

    private const BUILT_IN_FILES = [
        RunnerType::CLEAN_CSS => 'cleancss.js',
        RunnerType::LESS => 'lessc.js',
        RunnerType::TYPE_SCRIPT => 'tsc.js',
        RunnerType::UGLIFY => 'uglify.js',
        RunnerType::BROTLI => 'brotli.js',
    ];

    public function __construct(ConfigInterface $config, array $files = [])
    {
        $this->config = $config;
        $this->files  = array_merge(self::BUILT_IN_FILES, $files);
    }

    public function execute(string $type, ContentItem $item): string
    {
        if (!isset($this->files[$type])) {
            throw new \DomainException(sprintf('Unexpected type "%s"', $type));
        }

        $file    = File::makeAbsolutePath($this->files[$type], __DIR__ . DIRECTORY_SEPARATOR . 'js');
        $node_js = $this->config->getNodeJsExecutable();
        $cmd     = sprintf(
            "%s %s %s",
            $node_js->getBinary(),
            $file,
            File::makeAbsolutePath($item->file->path, $this->config->getProjectRoot())
        );
        $process = new Process($cmd, null, ['NODE_PATH' => $node_js->getNodeModulesLocation()], $item->getContent());
        $process->inheritEnvironmentVariables();

        $process->run();

        if (!$process->isSuccessful()) {
            throw new TranspileException(
                sprintf('Cannot compile "%s" due to compiler error.', $item->file->path),
                $process->getOutput() . $process->getErrorOutput()
            );
        }

        return $process->getOutput();
    }
}

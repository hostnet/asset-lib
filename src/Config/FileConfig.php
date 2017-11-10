<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Config;

use Hostnet\Component\Resolver\Bundler\Runner\RunnerInterface;
use Hostnet\Component\Resolver\Bundler\Runner\SingleProcessRunner;
use Hostnet\Component\Resolver\Bundler\Runner\UnixSocketFactory;
use Hostnet\Component\Resolver\Bundler\Runner\UnixSocketRunner;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Hostnet\Component\Resolver\Plugin\PluginInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class FileConfig implements ConfigInterface
{
    private $config_file;

    private $config_file_contents;

    private $dispatcher;

    private $logger;

    private $plugins;

    private $dev;

    /**
     * @param string $config_file
     * @param PluginInterface[] $plugins
     * @param bool $dev
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(
        string $config_file,
        array $plugins = [],
        bool $dev = false,
        EventDispatcherInterface $dispatcher = null,
        LoggerInterface $logger = null
    ) {
        $this->config_file          = $config_file;
        $this->config_file_contents = json_decode(file_get_contents($this->config_file), true);
        $this->dispatcher           = $dispatcher ?? new EventDispatcher();
        $this->logger               = $logger ?? new NullLogger();
        $this->plugins              = $plugins;
        $this->dev                  = $dev;
    }

    /**
     * Return if the application is running in dev.
     *
     * @return bool
     */
    public function isDev(): bool
    {
        return $this->dev;
    }

    /**
     * Return the current working directory.
     *
     * @return string
     */
    public function getProjectRoot(): string
    {
        return dirname($this->config_file);
    }

    /**
     * Return a list of additional include paths where node modules are located.
     *
     * @return string[]
     */
    public function getIncludePaths(): array
    {
        return $this->config_file_contents['include-paths'] ?? [];
    }

    /**
     * Return a list of entry point files. These are the files defined under 'files'.
     *
     * @return string[]
     */
    public function getEntryPoints(): array
    {
        return $this->config_file_contents['files'];
    }

    /**
     * Return a list of asset files. These are the files defined under 'assets'.
     *
     * @return string[]
     */
    public function getAssetFiles(): array
    {
        return $this->config_file_contents['assets'] ?? [];
    }

    /**
     * Returns a list of plugins.
     *
     * @return PluginInterface[]
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * Return the output folder in which to dump the compiled assets.
     *
     * @return string
     */
    public function getOutputFolder(bool $include_public_folder = true): string
    {
        $output_folder = $this->isDev()
            ? ($this->config_file_contents['output-folder-dev'] ?? 'dev')
            : ($this->config_file_contents['output-folder'] ?? 'dist');
        if (! $include_public_folder) {
            return $output_folder;
        }

        $web_root = $this->config_file_contents['web-root'];
        return rtrim($web_root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $output_folder;
    }

    /**
     * Return the source root folder in which the assets are located.
     *
     * @return string
     */
    public function getSourceRoot(): string
    {
        return $this->config_file_contents['source-root'] ?? '';
    }

    /**
     * Return the cache folder in which the temporary files can be put.
     *
     * @return string
     */
    public function getCacheDir(): string
    {
        return $this->getProjectRoot() . '/' . ($this->config_file_contents['cache-dir'] ?? 'var');
    }

    /**
     * @return Executable
     */
    public function getNodeJsExecutable(): Executable
    {
        return new Executable($this->getNodeJsBinary(), $this->getNodeModulesPath());
    }

    /**
     * NodeJS binary location.
     *
     * @return string
     */
    private function getNodeJsBinary(): string
    {
        return File::makeAbsolutePath($this->config_file_contents['node']['bin'], $this->getProjectRoot());
    }

    /**
     * Location of the 'node_modules' folder.
     *
     * @return string
     */
    private function getNodeModulesPath(): string
    {
        return File::makeAbsolutePath($this->config_file_contents['node']['node_modules'], $this->getProjectRoot());
    }

    /**
     * Returns a logger used for debugging asset buildings.
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Returns event dispatcher used for adding listeners to compiling assets.
     *
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    /**
     * Which runner should we use for the transitions?
     *
     * @return RunnerInterface
     */
    public function getRunner(): RunnerInterface
    {
        if ($this->config_file_contents['enable-unix-socket'] ?? false) {
            return new UnixSocketRunner($this, new UnixSocketFactory(), $this->logger);
        }

        return new SingleProcessRunner($this);
    }
}

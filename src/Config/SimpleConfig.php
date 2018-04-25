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
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Hostnet\Component\Resolver\Report\NullReporter;
use Hostnet\Component\Resolver\Report\ReporterInterface;
use Hostnet\Component\Resolver\Split\EntryPointSplittingStrategyInterface;
use Hostnet\Component\Resolver\Split\OneOnOneSplittingStrategy;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class SimpleConfig implements ConfigInterface
{
    private $is_dev;
    private $project_root;
    private $include_paths;
    private $entry_points;
    private $asset_files;
    private $web_root;
    private $output_folder;
    private $source_root;
    private $cache_dir;
    private $plugins;
    private $enable_unix_socket;
    private $node_js_executable;
    private $logger;
    private $event_dispatcher;
    private $runner;
    private $reporter;
    private $split_strategy;

    public function __construct(
        bool $is_dev,
        string $project_root,
        array $include_paths,
        array $entry_points,
        array $asset_files,
        string $web_root,
        string $output_folder,
        string $source_root,
        string $cache_dir,
        string $enable_unix_socket,
        array $plugins,
        Executable $node_js_executable,
        EventDispatcherInterface $event_dispatcher = null,
        LoggerInterface $logger = null,
        ReporterInterface $reporter = null,
        EntryPointSplittingStrategyInterface $split_strategy = null
    ) {
        $this->is_dev         = $is_dev;
        $this->project_root   = $project_root;
        $this->include_paths  = $include_paths;
        $this->entry_points   = $entry_points;
        $this->asset_files    = $asset_files;
        $this->web_root       = $web_root;
        $this->output_folder  = $output_folder;
        $this->source_root    = $source_root;
        $this->cache_dir      = $cache_dir;
        $this->plugins        = $plugins;
        $this->split_strategy = $split_strategy ? : new OneOnOneSplittingStrategy();

        $this->enable_unix_socket = $enable_unix_socket;
        $this->node_js_executable = $node_js_executable;
        $this->event_dispatcher   = $event_dispatcher ?? new EventDispatcher();
        $this->logger             = $logger ?? new NullLogger();
        $this->reporter           = $reporter ?? new NullReporter();
    }

    public function getSplitStrategy(): EntryPointSplittingStrategyInterface
    {
        return $this->split_strategy;
    }

    /**
     * {@inheritdoc}
     */
    public function isDev(): bool
    {
        return $this->is_dev;
    }

    /**
     * {@inheritdoc}
     */
    public function getIncludePaths(): array
    {
        return $this->include_paths;
    }

    /**
     * {@inheritdoc}
     */
    public function getProjectRoot(): string
    {
        return $this->project_root;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntryPoints(): array
    {
        return $this->entry_points;
    }

    /**
     * {@inheritdoc}
     */
    public function getAssetFiles(): array
    {
        return $this->asset_files;
    }

    /**
     * {@inheritdoc}
     */
    public function getOutputFolder(bool $include_public_folder = true): string
    {
        if (! $include_public_folder) {
            return $this->output_folder;
        }

        return $this->web_root . DIRECTORY_SEPARATOR . $this->output_folder;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceRoot(): string
    {
        return $this->source_root;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir(): string
    {
        return $this->cache_dir;
    }

    /**
     * {@inheritdoc}
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeJsExecutable(): Executable
    {
        return $this->node_js_executable;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->event_dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getSocketType(): string
    {
        return $this->enable_unix_socket;
    }

    /**
     * {@inheritdoc}
     */
    public function getRunner(): RunnerInterface
    {
        if (null === $this->runner) {
            $this->runner = $this->enable_unix_socket !== UnixSocketType::DISABLED
                ? new UnixSocketRunner($this, new UnixSocketFactory($this->logger))
                : new SingleProcessRunner($this);
        }

        return $this->runner;
    }

    /**
     * {@inheritdoc}
     */
    public function replaceReporter(ReporterInterface $reporter): ReporterInterface
    {
        $previous = $this->reporter;

        $this->reporter = $reporter;

        return $previous;
    }

    /**
     * {@inheritdoc}
     */
    public function getReporter(): ReporterInterface
    {
        return $this->reporter;
    }
}

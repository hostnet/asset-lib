<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Config;

use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Hostnet\Component\Resolver\Plugin\PluginInterface;
use Hostnet\Component\Resolver\Report\ReporterInterface;
use Hostnet\Component\Resolver\Split\EntryPointSplittingStrategyInterface;
use Psr\Log\LoggerInterface;

/**
 * Generic config reader. This loads the entry-points.json file.
 */
interface ConfigInterface
{
    /**
     * Return if the application is running in dev.
     */
    public function isDev(): bool;

    /**
     * Returns a class that resolves chunk points into which output file it should be sent to.
     */
    public function getSplitStrategy(): EntryPointSplittingStrategyInterface;

    /**
     * Return the current working directory.
     */
    public function getProjectRoot(): string;

    /**
     * Return a list of additional include paths where node modules are located.
     *
     * @return string[]
     */
    public function getIncludePaths(): array;

    /**
     * Return a list of entry point files. These are the files defined under 'files'.
     *
     * @return string[]
     */
    public function getEntryPoints(): array;

    /**
     * Return a list of asset files. These are the files defined under 'assets'.
     *
     * @return string[]
     */
    public function getAssetFiles(): array;

    /**
     * Returns a list of plugins.
     *
     * @return PluginInterface[]
     */
    public function getPlugins(): array;

    /**
     * Return the output folder in which to dump the compiled assets.
     *
     * @param bool $include_public_folder Whether to include the web/ directory
     */
    public function getOutputFolder(bool $include_public_folder = true): string;

    /**
     * Return the source root folder in which the assets are located.
     */
    public function getSourceRoot(): string;

    /**
     * Return the cache folder in which the temporary files can be put.
     */
    public function getCacheDir(): string;

    /**
     * Returns configuration where to find the node.js executable and node_modules path.
     */
    public function getNodeJsExecutable(): Executable;

    /**
     * Returns a logger used for debugging asset buildings.
     */
    public function getLogger(): LoggerInterface;

    /**
     * Set the reporter to use. This will override the current one and return
     * the previous once.
     *
     * @param ReporterInterface $reporter
     */
    public function replaceReporter(ReporterInterface $reporter): ReporterInterface;

    /**
     * Return the reporter.
     */
    public function getReporter(): ReporterInterface;
}

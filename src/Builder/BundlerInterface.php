<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

/**
 * Implementation of this interface allow for bundeling of files based on a build config.
 */
interface BundlerInterface
{
    /**
     * Bundle based on a build config.
     *
     * @param BuildConfig $build_config
     */
    public function bundleAll(BuildConfig $build_config): void;

    /**
     * Bundle based on a list of files.
     *
     * @param BuildConfig $build_config
     * @param string[]    $files
     */
    public function bundleFromFiles(BuildConfig $build_config, array $files): void;
}

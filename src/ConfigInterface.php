<?php

namespace Hostnet\Component\Resolver;


/**
 * Generic config reader. This loads the entry-points.json file.
 */
interface ConfigInterface
{
    /**
     * Return if the application is running in dev.
     *
     * @return bool
     */
    public function isDev(): bool;

    /**
     * Return the current working directory.
     *
     * @return string
     */
    public function cwd(): string;

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
     * Return the output folder in which to dump the compiled assets. This is
     * relative to the web root.
     *
     * @return string
     */
    public function getOutputFolder(): string;

    /**
     * Return the web root folder in which to dump the compiled assets.
     *
     * @return string
     */
    public function getWebRoot(): string;

    /**
     * Return the source root folder in which the assets are located.
     *
     * @return string
     */
    public function getSourceRoot(): string;

    /**
     * Return the cache folder in which the temporary files can be put.
     *
     * @return string
     */
    public function getCacheDir(): string;
}

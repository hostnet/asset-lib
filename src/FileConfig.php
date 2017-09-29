<?php

namespace Hostnet\Component\Resolver;

/**
 * Generic config reader. This loads the resolve.config.json file and allows
 * for flexible configuration of all the parts.
 */
class FileConfig implements ConfigInterface
{
    private $is_dev;
    private $data;
    private $cwd;

    public function __construct(bool $is_dev = false, string $config_file = 'resolve.config.json')
    {
        $this->is_dev = $is_dev;
        $this->data = json_decode(file_get_contents($config_file), true);
        $this->cwd = dirname($config_file);
    }

    /**
     * Return if the packer is running in dev mode.
     *
     * @return bool
     */
    public function isDev(): bool
    {
        return $this->is_dev;
    }

    /**
     * Return the current working directory.
     *
     * @return string
     */
    public function cwd(): string
    {
        return $this->cwd;
    }

    /**
     * Return a list of entry point files. These are the files defined under 'files'.
     *
     * @return string[]
     */
    public function getEntryPoints(): array
    {
        return $this->data['files'];
    }

    /**
     * Return a list of asset files. These are the files defined under 'assets'.
     *
     * @return string[]
     */
    public function getAssetFiles(): array
    {
        return $this->data['assets'] ?? [];
    }

    /**
     * Return the output folder in which to dump the compiled assets. This is
     * relative to the web root.
     *
     * @return string
     */
    public function getOutputFolder(): string
    {
        $output_folder = $this->isDev()
            ? ($this->data['output-folder-dev'] ?? 'dev')
            : ($this->data['output-folder'] ?? 'dist');

        return $output_folder;
    }

    /**
     * Return the web root folder in which to dump the compiled assets.
     *
     * @return string
     */
    public function getWebRoot(): string
    {
        return $this->data['web-root'];
    }

    /**
     * Return the source root folder in which the assets are located.
     *
     * @return string
     */
    public function getSourceRoot(): string
    {
        return $this->data['source-root'] ?? '';
    }

    /**
     * Return the cache folder in which the temporary files can be put.
     *
     * @return string
     */
    public function getCacheDir(): string
    {
        return $this->cwd() . '/' . ($this->data['cache-dir'] ?? 'var');
    }

    /**
     * NodeJS binary location.
     *
     * @return string
     */
    public function getNodeJsBinary(): string
    {
        if (File::isAbsolutePath($this->data['node']['bin'])) {
            return $this->data['node']['bin'];
        }

        return $this->cwd() . '/' . $this->data['node']['bin'];
    }

    /**
     * Location of the 'node_modules' folder.
     *
     * @return string
     */
    public function getNodeModulesPath(): string
    {
        if (File::isAbsolutePath($this->data['node']['node_modules'])) {
            return $this->data['node']['node_modules'];
        }

        return $this->cwd() . '/' . $this->data['node']['node_modules'];
    }

    /**
     * Check if Less is enabled.
     *
     * @return bool
     */
    public function isLessEnabled(): bool
    {
        return in_array('less', $this->data['loaders'] ?? [], true);
    }

    /**
     * Check if Typescript is enabled.
     *
     * @return bool
     */
    public function isTsEnabled(): bool
    {
        return in_array('ts', $this->data['loaders'] ?? [], true);
    }

    /**
     * Check if Angular is enabled.
     *
     * @return bool
     */
    public function isAngularEnabled(): bool
    {
        return in_array('angular', $this->data['loaders'] ?? [], true);
    }
}

<?php

namespace Hostnet\Component\Resolver;

/**
 * Generic config reader. This loads the entry-points.json file.
 */
class Config implements ConfigInterface
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

    public function isDev(): bool
    {
        return $this->is_dev;
    }

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
     * Return a key under the 'config' section.
     *
     * @param string $key
     * @return string
     */
    public function get(string $key): string
    {
        return $this->data['config'][$key];
    }

    /**
     * Return the output folder in which to dump the compiled assets. This is
     * relative to the web root.
     *
     * @return string
     */
    public function getOutputFolder(): string
    {
        $output_folder = $this->isDev() && isset($this->data['output-folder-dev'])
            ? $this->data['output-folder-dev']
            : $this->data['output-folder'];

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
        return $this->cwd() . '/' . $this->data['cache-dir'] ?? '/tmp';
    }
}

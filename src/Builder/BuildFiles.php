<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

use Hostnet\Component\Resolver\Cache\Cache;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\Dependency;
use Hostnet\Component\Resolver\Import\DependencyNodeInterface;
use Hostnet\Component\Resolver\Import\ImportFinderInterface;

class BuildFiles implements \JsonSerializable
{
    private $finder;
    private $extension_map;
    private $config;
    private $files = [];
    private $compiled = false;

    public function __construct(
        ImportFinderInterface $finder,
        ExtensionMap $extension_map,
        ConfigInterface $config
    ) {
        $this->finder        = $finder;
        $this->extension_map = $extension_map;
        $this->config        = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(bool $force = false): void
    {
        $output_folder = $this->config->getOutputFolder();
        $source_dir    = (!empty($this->config->getSourceRoot()) ? $this->config->getSourceRoot() . '/' : '');

        // put the require.js in the web folder
        $require_file        = new File(File::clean(__DIR__ . '/js/require.js'));
        $output_require_file = new File($output_folder . '/require.js');

        $this->addToFiles($output_require_file, [new Dependency($require_file)], true, $force);

        // Entry points
        foreach ($this->config->getEntryPoints() as $file_name) {
            $file           = new File($source_dir . $file_name);
            $entry_point    = new EntryPoint($this->finder->all($file), $this->config->getSplitStrategy());
            $files_to_build = $entry_point->getFilesToBuild($output_folder);

            if (empty($files_to_build)) {
                throw new \RuntimeException(
                    sprintf('%s did not resolve in any output file', $file_name)
                );
            }

            foreach ($files_to_build as $input => $dependencies) {
                $this->addToFiles(new File($input), $dependencies, false, $force);
            }
        }

        // Assets
        foreach ($this->config->getAssetFiles() as $file_name) {
            $file  = new File($source_dir . $file_name);
            $asset = new Asset($this->finder->all($file));

            $this->addToFiles($asset->getAssetFile($output_folder, $this->config->getSourceRoot()), $asset->getFiles(), false, $force);
        }

        $this->compiled = true;
    }

    private function addToFiles(File $base_file, array $dependencies, bool $skip_file_actions, bool $force)
    {
        $output_file = new File($base_file->dir . '/' . $base_file->getBaseName() . $this->extension_map->getResultingExtension('.' . $base_file->extension));

        $file_path = File::makeAbsolutePath($output_file->path, $this->config->getProjectRoot());
        $mtime     = file_exists($file_path) ? filemtime($file_path) : -1;

        if (!$force && ($this->config->isDev() && !$this->checkIfAnyChanged($output_file, $mtime, $dependencies))) {
            return;
        }

        $this->files[$output_file->getName()] = array_map(function (DependencyNodeInterface $dep) use ($skip_file_actions, $force, $mtime) {
            $file       = $dep->getFile();
            $path       = File::makeAbsolutePath($file->path, $this->config->getProjectRoot());
            $file_mtime = file_exists($path) ? filemtime($path) : -1;

            return [
                $file->path,
                '.' . $file->extension,
                $file->dir . '/' . $file->getBaseName(),
                $force || $mtime === -1 || $file_mtime === -1 || $mtime <= $file_mtime,
                $skip_file_actions
            ];
        }, $dependencies);
    }

    /**
     * Check if the output file is newer than the input files.
     *
     * @param File                      $output_file
     * @param int                       $mtime
     * @param DependencyNodeInterface[] $input_files
     * @return bool
     */
    private function checkIfAnyChanged(File $output_file, int $mtime, array $input_files): bool
    {
        // did the sources change?
        $sources_file  = $this->config->getCacheDir() . '/' . Cache::createFileCacheKey($output_file) . '.sources';
        $input_sources = array_map(function (DependencyNodeInterface $d) {
            return $d->getFile()->path;
        }, $input_files);

        sort($input_sources);

        if (!file_exists($sources_file)) {
            // make sure the cache dir exists
            if (!is_dir(dirname($sources_file))) {
                mkdir(dirname($sources_file), 0777, true);
            }
            file_put_contents($sources_file, serialize($input_sources));

            return true;
        }

        $sources = unserialize(file_get_contents($sources_file), []);
        if (count(array_diff($sources, $input_sources)) > 0 || count(array_diff($input_sources, $sources)) > 0) {
            file_put_contents($sources_file, serialize($input_sources));

            return true;
        }

        // Did the files change?
        if ($mtime === -1) {
            return true;
        }

        foreach ($input_files as $input_file) {
            $path = File::makeAbsolutePath($input_file->getFile()->path, $this->config->getProjectRoot());

            if ($mtime < filemtime($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'input' => $this->files
        ];
    }

    public function hasFiles()
    {
        if (!$this->compiled) {
            throw new \LogicException('Cannot count files if not yet compiled.');
        }

        return \count($this->files) > 0;
    }
}

<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\DependencyNodeInterface;
use Hostnet\Component\Resolver\Split\EntryPointSplittingStrategyInterface;
use Hostnet\Component\Resolver\Split\OneOnOneSplittingStrategy;

/**
 * Entry points are the starting files for your application. This can be main
 * files, stylesheets or javascript libraries.
 */
final class EntryPoint
{
    private $file;
    private $files       = [];
    private $asset_files = [];

    public function __construct(
        DependencyNodeInterface $file,
        EntryPointSplittingStrategyInterface $split_strategy = null
    ) {
        if ($split_strategy === null) {
            $split_strategy = new OneOnOneSplittingStrategy();
        }
        $this->file = $file->getFile();

        // Split the input files into bundle and vendor files.
        $walker = new TreeWalker(function (DependencyNodeInterface $dependency) use ($file, $split_strategy) {
            if ($dependency->isStatic()) {
                $this->asset_files[] = $dependency;
                return true;
            }

            // Make sure to have a .js extension.
            $file_obj    = $file->getFile();
            $output_file = File::clean($file_obj->dir . '/' . $file_obj->getBaseName() . '.js');

            $result = $split_strategy->resolveChunk($output_file, $dependency);
            if (!$result) {
                return false;
            }
            if (!isset($this->files[$result])) {
                $this->files[$result] = [];
            }
            $this->files[$result][] = $dependency;
            return true;
        });

        $walker->walk($file);
    }

    /**
     * Return the root file for the entry point.
     *
     * @return File
     */
    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * Returns the files to build
     *
     * @return array
     */
    public function getFilesToBuild(string $output_dir): array
    {
        $result = [];
        foreach ($this->files as $split_file => $dependencies) {
            $result[$output_dir . '/' . $split_file] = $dependencies;
        }
        foreach ($this->asset_files as $asset_file) {
            $result[$output_dir . '/' . $asset_file->getFile()->path] = [$asset_file];
        }
        return $result;
    }
}

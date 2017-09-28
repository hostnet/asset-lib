<?php
namespace Hostnet\Component\Resolver\Bundler;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\Dependency;
use Hostnet\Component\Resolver\Import\RootFile;

class Asset
{
    private $file;
    private $files;
    private $extension;

    /**
     * @param RootFile $file
     * @param string   $extension
     */
    public function __construct(RootFile $file, string $extension)
    {
        $this->file = $file->getFile();
        $this->files = [new Dependency($this->file)];
        $this->extension = $extension;

        $walker = new TreeWalker(function (Dependency $dependency) {
            $this->files[] = $dependency;
        });

        $walker->walk($file);
    }

    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * @return Dependency[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Return the asset file name.
     *
     * @param string $output_folder
     * @return File
     */
    public function getAssetFile(string $output_folder, string $source_folder): File
    {
        $base_dir = trim(substr($this->file->dir, strlen($source_folder)), '/');

        if (strlen($base_dir) > 0) {
            $base_dir .= '/';
        }

        return new File($output_folder . '/' . $base_dir . $this->file->getBaseName() . '.' . $this->extension);
    }
}

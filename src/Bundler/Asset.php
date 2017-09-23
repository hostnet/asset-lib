<?php
namespace Hostnet\Component\Resolver\Bundler;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\Dependency;

class Asset
{
    private $file;
    private $files;
    private $extension;

    /**
     * @param File         $file
     * @param Dependency[] $dependencies
     * @param string       $extension
     */
    public function __construct(File $file, array $dependencies, string $extension)
    {
        $this->file = $file;
        $this->files = array_merge([new Dependency($file)], $dependencies);
        $this->extension = $extension;
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
    public function getAssetFile(string $output_folder): File
    {
        return new File($output_folder . '/' . $this->file->dir . '/' . $this->file->getBaseName() . '.' . $this->extension);
    }
}

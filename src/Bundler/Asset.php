<?php
namespace Hostnet\Component\Resolver\Bundler;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\Dependency;

class Asset
{
    private $file;
    private $files;

    /**
     * @param File         $file
     * @param Dependency[] $dependencies
     */
    public function __construct(File $file, array $dependencies)
    {
        $this->file = $file;
        $this->files = array_merge([new Dependency($file)], $dependencies);
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
}

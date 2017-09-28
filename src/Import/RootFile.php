<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import;

use Hostnet\Component\Resolver\File;

class RootFile implements DependencyNodeInterface
{
    private $file;

    /**
     * @var Dependency[]|array
     */
    private $children = [];

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public function getFile(): File
    {
        return $this->file;
    }

    public function addChild(Dependency $dependency): void
    {
        $this->children[] = $dependency;
    }

    /**
     * @return array|Dependency[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}

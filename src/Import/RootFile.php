<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import;

use Hostnet\Component\Resolver\File;

class RootFile implements DependencyNodeInterface
{
    private $file;

    /**
     * @var DependencyNodeInterface[]|array
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

    public function addChild(DependencyNodeInterface $dependency): void
    {
        $this->children[] = $dependency;
    }

    /**
     * @return array|DependencyNodeInterface[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function isInlineDependency(): bool
    {
        return false;
    }

    public function isStatic(): bool
    {
        return false;
    }
}

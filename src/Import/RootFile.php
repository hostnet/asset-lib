<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import;

use Hostnet\Component\Resolver\File;

/**
 * The RootFile is the root node of a dependency tree.
 */
final class RootFile implements DependencyNodeInterface
{
    private $file;

    /**
     * @var DependencyNodeInterface[]
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

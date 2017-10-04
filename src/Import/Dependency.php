<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import;

use Hostnet\Component\Resolver\File;

/**
 * A dependency represent a link to another file.
 *
 * @see Import
 */
final class Dependency implements DependencyNodeInterface
{
    private $import;
    private $inline;
    private $static;

    /**
     * @var DependencyNodeInterface[]
     */
    private $children = [];

    public function __construct(File $import, bool $inline = false, bool $static = false)
    {
        $this->import = $import;
        $this->inline = $inline;
        $this->static = $static;
    }

    public function getFile(): File
    {
        return $this->import;
    }

    public function isInlineDependency(): bool
    {
        return $this->inline;
    }

    public function isStatic(): bool
    {
        return $this->static;
    }

    public function addChild(DependencyNodeInterface $dependency): void
    {
        $this->children[] = $dependency;
    }

    public function getChildren(): array
    {
        return $this->children;
    }
}

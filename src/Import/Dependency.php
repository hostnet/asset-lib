<?php

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
     * @var DependencyNodeInterface[]|array
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

    /**
     * Return if the dependency was an inline dependency. These are
     * dependencies which need to be checked but will be inlined in the final
     * result and thus must not be compiled.
     *
     * @return bool
     */
    public function isInlineDependency(): bool
    {
        return $this->inline;
    }

    /**
     * Return if the dependency was on a static file. This means that it is not
     * a javascript module but an asset.
     *
     * @return bool
     */
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

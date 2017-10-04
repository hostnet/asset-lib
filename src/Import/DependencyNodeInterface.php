<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import;

use Hostnet\Component\Resolver\File;

/**
 * Instances of this interface represent a node in a dependency tree.
 */
interface DependencyNodeInterface
{
    /**
     * Return the file for which dependency was created.
     *
     * @return File
     */
    public function getFile(): File;

    /**
     * Add a child dependency to this one.
     *
     * @param DependencyNodeInterface $dependency
     */
    public function addChild(DependencyNodeInterface $dependency): void;

    /**
     * Return all child dependencies.
     *
     * @return DependencyNodeInterface[]
     */
    public function getChildren(): array;

    /**
     * Return if the dependency was an inline dependency. These are
     * dependencies which need to be checked but will be inlined in the final
     * result and thus must not be compiled.
     *
     * @return bool
     */
    public function isInlineDependency(): bool;

    /**
     * Return if the dependency was on a static file. This means that it is not
     * a javascript module but an asset.
     *
     * @return bool
     */
    public function isStatic(): bool;
}

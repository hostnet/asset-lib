<?php

namespace Hostnet\Component\Resolver\Import;

/**
 * Implementations of this interface allow for finding imports of a file.
 */
interface ImportCollectorInterface
{
    /**
     * Check if this resolver is able to find imports for this file.
     *
     * @param ImportInterface $file
     * @return bool
     */
    public function supports(ImportInterface $file): bool;

    /**
     * Return all imports for the given file.
     *
     * @param string           $cwd
     * @param ImportInterface  $file
     * @param ImportCollection $imports
     */
    public function collect(string $cwd, ImportInterface $file, ImportCollection $imports);
}

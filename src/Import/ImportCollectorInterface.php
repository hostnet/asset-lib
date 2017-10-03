<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import;

use Hostnet\Component\Resolver\File;

/**
 * Implementations of this interface allow for finding imports of a file.
 */
interface ImportCollectorInterface
{
    /**
     * Check if this resolver is able to find imports for this file.
     *
     * @param File $file
     * @return bool
     */
    public function supports(File $file): bool;

    /**
     * Return all imports for the given file.
     *
     * @param string           $cwd
     * @param File             $file
     * @param ImportCollection $imports
     */
    public function collect(string $cwd, File $file, ImportCollection $imports);
}

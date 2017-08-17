<?php
namespace Hostnet\Component\Resolver\Import;
use Hostnet\Component\Resolver\File;

/**
 * Implementation of this interface allow for finding of imports.
 */
interface ImportFinderInterface
{
    /**
     * Resolve only the dependencies of a file.
     *
     * @param File $file
     * @return Dependency[]
     */
    public function get(File $file): array;

    /**
     * Resolve the entire dependency tree of a file.
     *
     * @param File $file
     * @return Dependency[]
     */
    public function all(File $file): array;
}

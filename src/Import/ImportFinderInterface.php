<?php
namespace Hostnet\Component\Resolver\Import;

/**
 * Implementation of this interface allow for finding of imports.
 */
interface ImportFinderInterface
{
    /**
     * Resolve only the dependencies of a file.
     *
     * @param ImportInterface $file
     * @return Dependency[]
     */
    public function get(ImportInterface $file): array;

    /**
     * Resolve the entire dependency tree of a file.
     *
     * @param ImportInterface $file
     * @return Dependency[]
     */
    public function all(ImportInterface $file): array;
}

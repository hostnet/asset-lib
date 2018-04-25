<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import;

use Hostnet\Component\Resolver\File;

/**
 * Collection of imports which are done by a file. These can be modules or
 * other resources.
 */
final class ImportCollection
{
    private $imports   = [];
    private $resources = [];

    /**
     * Add an import to the collection.
     *
     * @param Import $import
     */
    public function addImport(Import $import): void
    {
        $this->imports[] = $import;
    }

    /**
     * Add a resource to the collection.
     *
     * @param File $resource
     */
    public function addResource(File $resource): void
    {
        $this->resources[] = $resource;
    }

    /**
     * @return Import[]
     */
    public function getImports(): array
    {
        return $this->imports;
    }

    /**
     * @return File[]
     */
    public function getResources(): array
    {
        return $this->resources;
    }

    /**
     * Add the content of another ImportCollection to this one.
     *
     * @param ImportCollection $imports
     */
    public function extends(ImportCollection $imports): void
    {
        foreach ($imports->imports as $import) {
            $this->imports[] = $import;
        }
        foreach ($imports->resources as $resource) {
            $this->resources[] = $resource;
        }
    }
}

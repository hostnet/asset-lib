<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Import;

use Hostnet\Component\Resolver\File;

final class ImportCollection
{
    private $imports   = [];
    private $resources = [];

    public function addImport(Import $import)
    {
        $this->imports[] = $import;
    }

    public function addResource(File $resource)
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
    public function extends(ImportCollection $imports)
    {
        $this->imports   = array_merge($this->imports, $imports->getImports());
        $this->resources = array_merge($this->resources, $imports->getResources());
    }
}

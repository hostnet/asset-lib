<?php
namespace Hostnet\Component\Resolver\Import;

class ImportCollection
{
    private $imports = [];
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
        $this->imports = array_merge($this->imports, $imports->getImports());
        $this->resources = array_merge($this->resources, $imports->getResources());
    }
}

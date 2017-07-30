<?php

namespace Hostnet\Component\Resolver\Import;

/**
 * A dependency represent a link to another file.
 *
 * @see Import
 */
class Dependency
{
    private $import;
    private $virtual;
    private $static;

    public function __construct(ImportInterface $import, bool $virtual = false, bool $static = false)
    {
        $this->import = $import;
        $this->virtual = $virtual;
        $this->static = $static;
    }

    public function getImport(): ImportInterface
    {
        return $this->import;
    }

    /**
     * Return if the dependency was virtual. This means that there was a
     * dependency, but it should not appear in the compiled output result. This
     * is useful in cases where the transpiler in-lines the imported content
     * but you still want to track changes.
     *
     * @return bool
     */
    public function isVirtual(): bool
    {
        return $this->virtual;
    }

    public function isStatic(): bool
    {
        return $this->static;
    }
}

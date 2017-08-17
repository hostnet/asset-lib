<?php

namespace Hostnet\Component\Resolver\Import;
use Hostnet\Component\Resolver\File;

/**
 * A dependency represent a link to another file.
 *
 * @see Import
 */
final class Dependency
{
    private $import;
    private $virtual;
    private $static;

    public function __construct(File $import, bool $virtual = false, bool $static = false)
    {
        $this->import = $import;
        $this->virtual = $virtual;
        $this->static = $static;
    }

    public function getFile(): File
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
}

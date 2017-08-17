<?php
namespace Hostnet\Component\Resolver\Import;
use Hostnet\Component\Resolver\File;

/**
 * An import represents an import statement inside another file.
 */
class Import
{
    private $as;
    private $import;
    private $virtual;

    public function __construct(string $as, File $import, bool $virtual = false)
    {
        $this->as = $as;
        $this->import = $import;
        $this->virtual = $virtual;
    }

    /**
     * Return the alias that was used when importing the file. This can be the
     * module name or the relative path.
     *
     * @return string
     */
    public function getAs(): string
    {
        return $this->as;
    }

    /**
     * Return the imported file.
     *
     * @return File
     */
    public function getImportedFile(): File
    {
        return $this->import;
    }

    /**
     * Return if the import was virtual. This means that there was a
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
}

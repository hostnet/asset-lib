<?php

namespace Hostnet\Component\Resolver\Import;

/**
 * Implementations of this interface represent an import from a source.
 */
interface ImportInterface
{
    /**
     * Return the name of the import. This is usually the file or module name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Return the name of the import without any extensions.
     *
     * @return string
     */
    public function getBaseName(): string;

    /**
     * Return the path of file to import.
     *
     * @return string
     */
    public function getPath(): string;

    /**
     * Return the extension for the imported file.
     *
     * @return string
     */
    public function getExtension(): string;

    /**
     * Return the directory the file or module is located.
     *
     * @return string
     */
    public function getDirectory(): string;

    /**
     * Check of another ImportInterface is equal to this.
     *
     * @param ImportInterface $other
     * @return bool
     */
    public function equals(ImportInterface $other): bool;
}

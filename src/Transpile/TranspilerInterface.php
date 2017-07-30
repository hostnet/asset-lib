<?php

namespace Hostnet\Component\Resolver\Transpile;

use Hostnet\Component\Resolver\Import\ImportInterface;


/**
 * Transpiler which supports multiple extensions. It can only have one
 * sub-transpiler per extension registered.
 */
interface TranspilerInterface
{
    /**
     * Return the extension of the file which will be the outputted result.
     *
     * @param ImportInterface $file
     * @return string
     */
    public function getExtensionFor(ImportInterface $file): string;

    /**
     * Transpile a file into browser usable code and return it.
     *
     * @param ImportInterface $file
     * @throw \InvalidArgumentException when file was given that was not supported.
     * @return TranspileResult
     */
    public function transpile(ImportInterface $file): TranspileResult;
}

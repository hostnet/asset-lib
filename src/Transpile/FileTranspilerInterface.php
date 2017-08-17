<?php

namespace Hostnet\Component\Resolver\Transpile;

use Hostnet\Component\Resolver\File;

/**
 * Implementations of this interface allow for transpiling of a file by the
 * supported extension.
 */
interface FileTranspilerInterface
{
    /**
     * Return the supported extension without the '.'. I.e., less, ts, js, css, etc.
     *
     * @return string
     */
    public function getSupportedExtension(): string;

    /**
     * Return the file extension that will result from transpiling the asset.
     *
     * @return string
     */
    public function getOutputtedExtension(): string;

    /**
     * Transpile a file.
     *
     * @param string $cwd
     * @param File   $file
     * @throws TranspileException when transpiling has failed.
     * @return TranspileResult
     */
    public function transpile(string $cwd, File $file): TranspileResult;
}

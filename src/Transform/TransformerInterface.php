<?php

namespace Hostnet\Component\Resolver\Transform;

use Hostnet\Component\Resolver\Import\ImportInterface;

interface TransformerInterface
{
    /**
     * Apply a transformation to the output of the transpile result.
     *
     * @param ImportInterface $file
     * @param string          $content
     * @param string          $output_dir
     * @return string
     */
    public function onPostTranspile(ImportInterface $file, string $content, string $output_dir): string;

    /**
     * Apply a transformation to the file content that is being written to disk.
     *
     * @param ImportInterface $file
     * @param string          $content
     * @param string          $output_dir
     * @return string
     */
    public function onPreWrite(ImportInterface $file, string $content, string $output_dir): string;
}

<?php

namespace Hostnet\Component\Resolver\Transform;

use Hostnet\Component\Resolver\File;

interface TransformerInterface
{
    /**
     * Apply a transformation to the output of the transpile result.
     *
     * @param File   $file
     * @param string $content
     * @param string $output_dir
     * @return string
     */
    public function onPostTranspile(File $file, string $content, string $output_dir): string;

    /**
     * Apply a transformation to the file content that is being written to disk.
     *
     * @param File   $file
     * @param string $content
     * @param string $output_dir
     * @return string
     */
    public function onPreWrite(File $file, string $content, string $output_dir): string;
}

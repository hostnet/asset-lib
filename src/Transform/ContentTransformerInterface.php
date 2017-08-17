<?php

namespace Hostnet\Component\Resolver\Transform;

use Hostnet\Component\Resolver\File;

interface ContentTransformerInterface
{
    /**
     * Check if this transformer is able to transform for this file.
     *
     * @param File $file
     * @return bool
     */
    public function supports(File $file): bool;

    /**
     * Transform the content before it is written to a file.
     *
     * @param File    $file
     * @param string  $content
     * @param string  $cwd
     * @param string  $output_dir
     * @throws TransformException
     * @return string
     */
    public function transform(File $file, string $content, string $cwd, string $output_dir): string;
}

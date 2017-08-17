<?php

namespace Hostnet\Component\Resolver\Transform;

use Hostnet\Component\Resolver\Import\ImportInterface;

interface ContentTransformerInterface
{
    /**
     * Check if this transformer is able to transform for this file.
     *
     * @param ImportInterface $file
     * @return bool
     */
    public function supports(ImportInterface $file): bool;

    /**
     * Transform the content before it is written to a file.
     *
     * @param ImportInterface $file
     * @param string          $content
     * @param string          $cwd
     * @param string          $output_dir
     * @throws TransformException
     * @return string
     */
    public function transform(ImportInterface $file, string $content, string $cwd, string $output_dir): string;
}

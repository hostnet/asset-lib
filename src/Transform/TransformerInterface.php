<?php

namespace Hostnet\Component\Resolver\Transform;

use Hostnet\Component\Resolver\Import\ImportInterface;

interface TransformerInterface
{
    /**
     * Apply a transformation to the output result.
     *
     * @param ImportInterface $file
     * @param string          $content
     * @param string          $output_dir
     * @return string
     */
    public function transform(ImportInterface $file, string $content, string $output_dir): string;
}

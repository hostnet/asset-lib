<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\FileSystem;

use Hostnet\Component\Resolver\File;

/**
 * Implementations of this interface allow for writing of a file.
 */
interface WriterInterface
{
    /**
     * Write a file and it's content.
     *
     * @param File   $file
     * @param string $content
     */
    public function write(File $file, string $content): void;
}

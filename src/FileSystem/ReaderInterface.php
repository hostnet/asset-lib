<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\FileSystem;

use Hostnet\Component\Resolver\File;

/**
 * Implementations of this interface allow for reading of a file.
 */
interface ReaderInterface
{
    /**
     * Read a file to a string.
     *
     * @param File $file
     * @return string
     */
    public function read(File $file): string;
}

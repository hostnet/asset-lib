<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\FileSystem;

use Hostnet\Component\Resolver\File;

interface ReaderInterface
{
    public function read(File $file): string;
}

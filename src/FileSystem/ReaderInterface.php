<?php
namespace Hostnet\Component\Resolver\FileSystem;

use Hostnet\Component\Resolver\File;

interface ReaderInterface
{
    public function read(File $file): string;
}

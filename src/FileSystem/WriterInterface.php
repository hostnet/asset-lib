<?php

namespace Hostnet\Component\Resolver\FileSystem;


use Hostnet\Component\Resolver\File;

interface WriterInterface
{
    public function write(File $file, string $content): void;
}

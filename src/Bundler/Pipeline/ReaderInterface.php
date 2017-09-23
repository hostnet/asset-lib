<?php
namespace Hostnet\Component\Resolver\Bundler\Pipeline;

use Hostnet\Component\Resolver\File;

interface ReaderInterface
{
    public function read(File $file): string;
}

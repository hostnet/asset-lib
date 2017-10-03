<?php

namespace Hostnet\Component\Resolver\FileSystem;


use Hostnet\Component\Resolver\File;

class FileWriter implements WriterInterface
{
    private $cwd;

    public function __construct(string $cwd)
    {
        $this->cwd = $cwd;
    }

    public function write(File $file, string $content): void
    {
        $path = $this->cwd . DIRECTORY_SEPARATOR . $file->path;

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $content);
    }
}

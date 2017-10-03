<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\FileSystem;

use Hostnet\Component\Resolver\File;

class FileReader implements ReaderInterface
{
    private $cwd;

    public function __construct(string $cwd)
    {
        $this->cwd = $cwd;
    }

    public function read(File $file): string
    {
        return file_get_contents($this->cwd . DIRECTORY_SEPARATOR . $file->path);
    }
}

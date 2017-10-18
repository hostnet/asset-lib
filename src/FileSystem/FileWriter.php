<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\FileSystem;

use Hostnet\Component\Resolver\File;

/**
 * Implementation of the WriterInterface which writes it to disk.
 */
final class FileWriter implements WriterInterface
{
    private $cwd;

    public function __construct(string $cwd)
    {
        $this->cwd = $cwd;
    }

    /**
     * @param File   $file
     * @param string $content
     */
    public function write(File $file, string $content): void
    {
        $path = File::makeAbsolutePath($file->path, $this->cwd);

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $content);
    }
}

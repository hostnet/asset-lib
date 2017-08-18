<?php
namespace Hostnet\Component\Resolver\Transpile\BuildIn;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Transpile\FileTranspilerInterface;
use Hostnet\Component\Resolver\Transpile\TranspileResult;

final class JsFileTranspiler implements FileTranspilerInterface
{
    public function getSupportedExtension(): string
    {
        return 'js';
    }

    public function getOutputtedExtension(): string
    {
        return 'js';
    }

    public function transpile(string $cwd, File $file): TranspileResult
    {
        return new TranspileResult($file->getName(), file_get_contents($cwd . '/' . $file->path));
    }
}

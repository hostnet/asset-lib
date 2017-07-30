<?php
namespace Hostnet\Component\Resolver\Transpile\BuildIn;

use Hostnet\Component\Resolver\Import\ImportInterface;
use Hostnet\Component\Resolver\Transpile\FileTranspilerInterface;
use Hostnet\Component\Resolver\Transpile\JsModuleWrapperInterface;
use Hostnet\Component\Resolver\Transpile\TranspileResult;

class JsFileTranspiler implements FileTranspilerInterface
{
    public function getSupportedExtension(): string
    {
        return 'js';
    }

    public function getOutputtedExtension(): string
    {
        return 'js';
    }

    public function transpile(string $cwd, ImportInterface $file): TranspileResult
    {
        return new TranspileResult($file->getName(), file_get_contents($cwd . '/' . $file->getPath()));
    }
}

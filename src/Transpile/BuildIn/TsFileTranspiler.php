<?php
namespace Hostnet\Component\Resolver\Transpile\BuildIn;

use Hostnet\Component\Resolver\Import\ImportInterface;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Hostnet\Component\Resolver\Transpile\FileTranspilerInterface;
use Hostnet\Component\Resolver\Transpile\JsModuleWrapperInterface;
use Hostnet\Component\Resolver\Transpile\TranspileException;
use Hostnet\Component\Resolver\Transpile\TranspileResult;
use Symfony\Component\Process\Process;

class TsFileTranspiler implements FileTranspilerInterface
{
    private $nodejs;

    public function __construct(Executable $nodejs)
    {
        $this->nodejs = $nodejs;
    }

    public function getSupportedExtension(): string
    {
        return 'ts';
    }

    public function getOutputtedExtension(): string
    {
        return 'js';
    }

    public function transpile(string $cwd, ImportInterface $file): TranspileResult
    {
        $process = new Process($this->nodejs->getBinary() . ' ' . __DIR__ . '/js/tsc.js ' . $cwd . '/' . $file->getPath(), null, [
            'NODE_PATH' => $this->nodejs->getNodeModulesLocation()
        ]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new TranspileException(
                sprintf('Cannot compile "%s" due to compiler error.', $file->getPath()),
                $process->getOutput() . $process->getErrorOutput()
            );
        }

        return new TranspileResult(
            $file->getDirectory() . '/' . $file->getBaseName(),
            $process->getOutput()
        );
    }
}

<?php
namespace Hostnet\Component\Resolver\Transpile\BuildIn;

use Hostnet\Component\Resolver\Import\ImportInterface;
use Hostnet\Component\Resolver\Transpile\FileTranspilerInterface;
use Hostnet\Component\Resolver\Transpile\TranspileException;
use Hostnet\Component\Resolver\Transpile\TranspileResult;
use Symfony\Component\Process\Process;

class LessFileTranspiler implements FileTranspilerInterface
{
    private $lessc_location;

    public function __construct(string $lessc_location)
    {
        $this->lessc_location = $lessc_location;
    }

    public function getSupportedExtension(): string
    {
        return 'less';
    }

    public function getOutputtedExtension(): string
    {
        return 'css';
    }

    public function transpile(string $cwd, ImportInterface $file): TranspileResult
    {
        $process = new Process($cwd . '/vendor/bin/node ' . $cwd . '/' . $this->lessc_location . ' --source-map-less-inline ' . $cwd . '/' .$file->getPath());
        $process->run();

        if (!$process->isSuccessful()) {
            throw new TranspileException(
                sprintf('Cannot compile "%s" due to compiler error.', $file->getPath()),
                $process->getErrorOutput()
            );
        }

        return new TranspileResult($file->getName(), $process->getOutput());
    }
}

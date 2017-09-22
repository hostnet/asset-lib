<?php
namespace Hostnet\Component\Resolver\Transpile\BuildIn;

use Hostnet\Component\Resolver\Bundler\Item;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Hostnet\Component\Resolver\Transpile\FileTranspilerInterface;
use Hostnet\Component\Resolver\Transpile\TranspileException;
use Hostnet\Component\Resolver\Transpile\TranspileResult;
use Symfony\Component\Process\Process;

final class LessFileTranspiler implements FileTranspilerInterface
{
    private $nodejs;

    public function __construct(Executable $nodejs)
    {
        $this->nodejs = $nodejs;
    }

    public function getSupportedExtension(): string
    {
        return 'less';
    }

    public function getOutputtedExtension(): string
    {
        return 'css';
    }

    public function transpile(string $cwd, Item $item): void
    {
        $process = new Process($this->nodejs->getBinary() . ' ' . __DIR__ . '/js/lessc.js ' . $cwd . '/' . $item->file->path, null, [
            'NODE_PATH' => $this->nodejs->getNodeModulesLocation()
        ]);
        $process->inheritEnvironmentVariables();
        $process->setInput($item->getContent());
        $process->run();

        if (!$process->isSuccessful()) {
            throw new TranspileException(
                sprintf('Cannot compile "%s" due to compiler error.', $item->file->path),
                $process->getOutput() . $process->getErrorOutput()
            );
        }

        $item->transition(Item::READY, $process->getOutput());
    }
}

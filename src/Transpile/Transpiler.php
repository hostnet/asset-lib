<?php
namespace Hostnet\Component\Resolver\Transpile;

use Hostnet\Component\Resolver\Import\ImportInterface;

/**
 * Transpiler which supports multiple extensions. It can only have one
 * sub-transpiler per extension registered.
 */
class Transpiler implements TranspilerInterface
{
    private $cwd;

    /**
     * @var FileTranspilerInterface[]
     */
    private $transpilers = [];

    public function __construct(string $cwd)
    {
        $this->cwd = $cwd;
    }

    public function addTranspiler(FileTranspilerInterface $transpiler): void
    {
        $ext = $transpiler->getSupportedExtension();

        if (isset($this->transpilers[$ext])) {
            throw new \InvalidArgumentException(sprintf('Transpiler already registered for extension "%s".', $ext));
        }

        $this->transpilers[$ext] = $transpiler;
    }

    public function getExtensionFor(ImportInterface $file): string
    {
        $ext = $file->getExtension();

        if (!isset($this->transpilers[$ext])) {
            throw new \InvalidArgumentException(sprintf('No transpiler registered for extension "%s".', $ext));
        }

        return $this->transpilers[$ext]->getOutputtedExtension();
    }

    public function transpile(ImportInterface $file): TranspileResult
    {
        $ext = $file->getExtension();

        if (!isset($this->transpilers[$ext])) {
            throw new \InvalidArgumentException(sprintf('No transpiler registered for extension "%s".', $ext));
        }

        return $this->transpilers[$ext]->transpile($this->cwd, $file);
    }
}

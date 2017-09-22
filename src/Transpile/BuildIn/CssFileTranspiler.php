<?php
namespace Hostnet\Component\Resolver\Transpile\BuildIn;

use Hostnet\Component\Resolver\Bundler\Item;
use Hostnet\Component\Resolver\Transpile\FileTranspilerInterface;

final class CssFileTranspiler implements FileTranspilerInterface
{
    public function getSupportedExtension(): string
    {
        return 'css';
    }

    public function getOutputtedExtension(): string
    {
        return 'css';
    }

    public function transpile(string $cwd, Item $item): void
    {
        $item->transition(Item::READY);
        // do nothing.
    }
}

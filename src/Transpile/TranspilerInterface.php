<?php

namespace Hostnet\Component\Resolver\Transpile;

use Hostnet\Component\Resolver\Bundler\Item;
use Hostnet\Component\Resolver\File;


/**
 * Transpiler which supports multiple extensions. It can only have one
 * sub-transpiler per extension registered.
 */
interface TranspilerInterface
{
    /**
     * Return the extension of the file which will be the outputted result.
     *
     * @param File $file
     * @return string
     */
    public function getExtensionFor(File $file): string;

    /**
     * Transpile a file into browser usable code and return it.
     *
     * @param Item $item
     * @throw \InvalidArgumentException when file was given that was not supported.
     */
    public function transpile(Item $item): void;
}

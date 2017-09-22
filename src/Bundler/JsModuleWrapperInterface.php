<?php

namespace Hostnet\Component\Resolver\Bundler;

/**
 * Implementations of this interface allow for wrapping of modules files in
 * such a way they can be used by require.js.
 */
interface JsModuleWrapperInterface
{
    /**
     * Wrap javascript content. The given file name should also support module names.
     *
     * @param Item $item
     */
    public function wrap(Item $item): void;
}

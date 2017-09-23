<?php

namespace Hostnet\Component\Resolver\Transpile;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;

/**
 * Implementations of this interface allow for transpiling of a file by the
 * supported extension.
 */
interface FileTranspilerInterface
{
    public function supports(ContentState $state): bool;

    /**
     * Peek for the extension of the state. This should mimic the transpile.
     */
    public function peek(string $cwd, ContentState $state): void;

    /**
     * Transpile a file.
     *
     * @param string      $cwd
     * @param ContentItem $item
     * @throws TranspileException when transpiling has failed.
     */
    public function transpile(string $cwd, ContentItem $item): void;
}

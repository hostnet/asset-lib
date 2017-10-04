<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler\Pipeline;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Bundler\TranspileException;

/**
 * Implementations of this interface allow for transpiling of a file by the
 * supported extension.
 */
interface ContentProcessorInterface
{
    /**
     * Return if the content state is supported by the processor.
     *
     * @param ContentState $state
     * @return bool
     */
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

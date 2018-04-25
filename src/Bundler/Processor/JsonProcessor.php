<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler\Processor;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;

/**
 * Processes JSON files. These will be converted to JavaScript modules.
 */
final class JsonProcessor implements ContentProcessorInterface
{
    public function supports(ContentState $state): bool
    {
        return $state->current() === ContentState::UNPROCESSED && $state->extension() === 'json';
    }

    public function peek(string $cwd, ContentState $state): void
    {
        $state->transition(ContentState::PROCESSED, 'js');
    }

    public function transpile(string $cwd, ContentItem $item): void
    {
        $js  = 'return ';
        $js .= $item->getContent();
        $js .= ";\n";

        $item->transition(ContentState::PROCESSED, $js, 'js');
    }
}

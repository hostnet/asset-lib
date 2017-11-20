<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler\Processor;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;

/**
 * Wrap a javascript file such that can be used as a module.
 */
final class ModuleProcessor implements ContentProcessorInterface
{
    public function supports(ContentState $state): bool
    {
        return $state->current() === ContentState::PROCESSED && $state->extension() === 'js';
    }

    public function peek(string $cwd, ContentState $state): void
    {
        $state->transition(ContentState::READY);
    }

    public function transpile(string $cwd, ContentItem $item): void
    {
        $js = "register("
            . json_encode($item->module_name, JSON_UNESCAPED_SLASHES)
            . ", function (define, require, module, exports) {\n"
            . $item->getContent()
            . "\n});\n";

        $item->transition(ContentState::READY, $js);
    }
}

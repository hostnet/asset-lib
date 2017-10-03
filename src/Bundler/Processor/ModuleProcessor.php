<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler\Processor;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Bundler\Pipeline\ContentProcessorInterface;

/**
 * Wrap a javascript file such that can be used as a module.
 */
class ModuleProcessor implements ContentProcessorInterface
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
        $js  = "register('" . $item->module_name . "', function (define, require, module, exports) {\n";
        $js .= $item->getContent();
        $js .= "\n});\n";

        $item->transition(ContentState::READY, $js);
    }
}

<?php
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
        $js = "(function (define) {\n";
        $js .= $item->getContent();
        $js .= "\n})((function() {
            var _define = function (a, b, c) {
                typeof a !== 'string' ? define('" . $item->module_name . "', a, b) : define(a, b, c);
            };
            _define.amd = {};
    
            return _define;
        })());\n";

        $item->transition(ContentState::READY, $js);
    }
}

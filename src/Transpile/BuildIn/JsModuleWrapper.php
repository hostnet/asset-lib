<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Transpile\BuildIn;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Transpile\FileTranspilerInterface;

/**
 * Wrap a javascript file such that can be used as a module.
 */
class JsModuleWrapper implements FileTranspilerInterface
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
        $js .= "\n})((function(module_name) {
            var _define = function (a, b, c) {
                if (!c) {
                    if (typeof a === 'string' && typeof b === 'function') {
                        define(a, [], b);
                    } else {
                        define(module_name, a, b);
                    }
                } else {
                    define(a, b, c);
                }
            };
            _define.amd = {};
    
            return _define;
        })('" . $item->module_name . "'));\n";

        $item->transition(ContentState::READY, $js);
    }
}

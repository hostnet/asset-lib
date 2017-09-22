<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler;

/**
 * Wrap a javascript file such that can be used as a module.
 */
class JsModuleWrapper implements JsModuleWrapperInterface
{
    /**
     * {@inheritdoc}
     */
    public function wrap(Item $item): void
    {
        $js = "(function (define) {\n";
        $js .= $item->getContent();
        $js .= "\n})(__create_define('" . $item->module_name . "'));\n";

        $item->transition(Item::READY, $js);
    }
}

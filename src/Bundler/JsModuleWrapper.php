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
    public function wrapModule(string $initializer, string $module_name, string $content): string
    {
        $js = "(function (define) {\n";
        $js .= $content;
        $js .= "\n})(" . $initializer . "('" . $module_name . "'));\n";

        return $js;
    }
}

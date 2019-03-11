<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Builder\Step\CssFontRewriteStep;
use Hostnet\Component\Resolver\Builder\Step\IdentityBuildStep;

/**
 * Add support for re-writing font files in .css files so they end up in the correct folder and are referenced
 * correctly.
 */
final class CssFontRewritePlugin implements PluginInterface
{
    public function activate(PluginApi $plugin_api): void
    {
        $plugin_api->addBuildStep(new CssFontRewriteStep());

        $plugin_api->addBuildStep(new IdentityBuildStep('.eot'));
        $plugin_api->addBuildStep(new IdentityBuildStep('.otf'));
        $plugin_api->addBuildStep(new IdentityBuildStep('.ttf'));
        $plugin_api->addBuildStep(new IdentityBuildStep('.woff'));
        $plugin_api->addBuildStep(new IdentityBuildStep('.woff2'));
        $plugin_api->addBuildStep(new IdentityBuildStep('.svg'));
    }
}

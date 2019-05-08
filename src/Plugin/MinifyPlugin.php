<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Builder\Step\CleanCssBuildStep;
use Hostnet\Component\Resolver\Builder\Step\UglifyBuildStep;

/**
 * Enables minifying for JavaScript and css.
 *
 * The goal is to remove all unnecessary characters from source code without
 * changing its functionality.
 *
 * Doing so makes your website faster. Less code to download, less code to
 * execute. Awesome, right?
 *
 * This process is not fast, though! Enabling this in dev mode will give you a
 * shitty experience. Only enable this in prod mode!
 */
final class MinifyPlugin implements PluginInterface
{
    public function activate(PluginApi $plugin_api): void
    {
        $plugin_api->addBuildStep(new UglifyBuildStep());
        $plugin_api->addBuildStep(new CleanCssBuildStep());
    }
}

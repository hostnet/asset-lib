<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Event\AssetEvents;
use Hostnet\Component\Resolver\Event\FileEvents;
use Hostnet\Component\Resolver\EventListener\CleanCssListener;
use Hostnet\Component\Resolver\EventListener\UglifyJsListener;

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
        $uglify_listener    = new UglifyJsListener($plugin_api->getRunner());
        $clean_css_listener = new CleanCssListener($plugin_api->getRunner());

        $dispatcher = $plugin_api->getConfig()->getEventDispatcher();
        $dispatcher->addListener(FileEvents::PRE_WRITE, [$uglify_listener, 'onPreWrite']);
        $dispatcher->addListener(AssetEvents::READY, [$clean_css_listener, 'onPreWrite']);
    }
}

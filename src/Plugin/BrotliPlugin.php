<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Event\FileEvents;
use Hostnet\Component\Resolver\EventListener\BrotliListener;

/**
 * This plugin outputs a brotli-compressed file next to the output file.
 * Enabling this with the correct web server settings will serve a static brotli compressed file if the browser
 * accepts brotli encoding. Brotli is superior in compression size compared to gzip, but it is slower to dynamically
 * compress it, so a pre-compressed asset is recommended.
 */
class BrotliPlugin implements PluginInterface
{
    public function activate(PluginApi $plugin_api): void
    {
        $config          = $plugin_api->getConfig();
        $dispatcher      = $config->getEventDispatcher();
        $brotli_listener = new BrotliListener($plugin_api->getRunner(), $dispatcher, $config->getProjectRoot());
        $dispatcher->addListener(FileEvents::POST_WRITE, [$brotli_listener, 'onPostWrite']);
    }
}

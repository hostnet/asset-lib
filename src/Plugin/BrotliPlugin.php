<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Builder\Writer\BrotliFileWriter;

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
        $plugin_api->addWriter(new BrotliFileWriter());
    }
}

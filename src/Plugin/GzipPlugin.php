<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Builder\Writer\GzipFileWriter;

/**
 * This plugin outputs a gzipped file next to the output file. Enabling this with the correct web server settings
 * will serve a static gzipped file if the browser accepts gzip encoding.
 * For example in Apache this can be done with a simple .htaccess file.
 */
class GzipPlugin implements PluginInterface
{
    public function activate(PluginApi $plugin_api): void
    {
        $plugin_api->addWriter(new GzipFileWriter());
    }
}

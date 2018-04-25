<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Event\FileEvent;
use Hostnet\Component\Resolver\Event\FileEvents;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\FileWriter;

/**
 * This plugin outputs a gzipped file next to the output file. Enabling this with the correct web server settings
 * will serve a static gzipped file if the browser accepts gzip encoding.
 * For example in Apache this can be done with a simple .htaccess file.
 */
class GzipPlugin implements PluginInterface
{
    public function activate(PluginApi $plugin_api): void
    {
        $config     = $plugin_api->getConfig();
        $dispatcher = $config->getEventDispatcher();
        $dispatcher->addListener(FileEvents::POST_WRITE, function (FileEvent $ev) use ($config, $dispatcher) {
            $file    = $ev->getFile();
            $content = $ev->getContent();
            // if the file is already compressed with brotli/gzip, do not compress it again as we do not serve files
            // like .br.gz.br
            if (preg_match('/\.(gz|br)$/', $file->path)) {
                return;
            }
            $gzip_content = gzencode($content, 9);
            if (strlen($gzip_content) >= strlen($content)) {
                return;
            }

            $writer = new FileWriter($dispatcher, $config->getProjectRoot());
            $writer->write(new File($ev->getFile()->path . '.gz'), $gzip_content);
        });
    }
}

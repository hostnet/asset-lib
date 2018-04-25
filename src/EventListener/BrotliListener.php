<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\EventListener;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\Runner\RunnerInterface;
use Hostnet\Component\Resolver\Bundler\Runner\RunnerType;
use Hostnet\Component\Resolver\Event\FileEvent;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\FileWriter;
use Hostnet\Component\Resolver\FileSystem\StringReader;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * The brotli listener will output another file with a .br extension contain a brotli compressed asset.
 *
 * @see https://github.com/devongovett/brotli.js
 */
class BrotliListener
{
    private $runner;

    private $dispatcher;

    private $project_root;

    public function __construct(RunnerInterface $runner, EventDispatcher $dispatcher, string $project_root)
    {
        $this->runner       = $runner;
        $this->dispatcher   = $dispatcher;
        $this->project_root = $project_root;
    }

    /**
     * @param FileEvent $event
     */
    public function onPostWrite(FileEvent $event): void
    {
        $file = $event->getFile();
        // if the file is already compressed with brotli/gzip, do not compress it again as we do not serve files
        // like .br.gz.br
        if (preg_match('/\.(gz|br)$/', $file->path)) {
            return;
        }
        $content         = $event->getContent();
        $item            = new ContentItem($file, $file->getName(), new StringReader($content));
        $brotli_contents = $this->runner->execute(RunnerType::BROTLI, $item);
        // the runner returns an empty string if it could not be brotli compressed. This seems to be the
        // case for some of the binary files. Maybe we should blacklist binary files, but in general
        // any file could benefit from brotli compression.
        if (empty($brotli_contents) || strlen($brotli_contents) >= strlen($content)) {
            return;
        }

        $writer = new FileWriter($this->dispatcher, $this->project_root);
        $writer->write(new File($file->path . '.br'), $brotli_contents);
    }
}

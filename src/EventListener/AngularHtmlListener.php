<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\EventListener;

use Hostnet\Component\Resolver\Bundler\Asset;
use Hostnet\Component\Resolver\Bundler\Pipeline\ContentPipelineInterface;
use Hostnet\Component\Resolver\ConfigInterface;
use Hostnet\Component\Resolver\Event\AssetEvent;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\FileReader;
use Hostnet\Component\Resolver\FileSystem\ReaderInterface;
use Hostnet\Component\Resolver\Import\Dependency;
use Hostnet\Component\Resolver\Import\ImportFinderInterface;

/**
 * The Angular listener checks all angular component files. If they contain a
 * templateUrl or a styleUrls these files processed and inlined in the
 * component. This will boost performance since you only have to load a single
 * file containing all information. Furthermore it allows you to use more
 * flexible resources like less files for stylesheets.
 */
final class AngularHtmlListener
{
    private $config;
    private $pipeline;
    private $finder;

    public function __construct(
        ConfigInterface $config,
        ContentPipelineInterface $pipeline,
        ImportFinderInterface $finder
    ) {
        $this->config   = $config;
        $this->pipeline = $pipeline;
        $this->finder   = $finder;
    }

    /**
     * @param AssetEvent $event
     */
    public function onPostTranspile(AssetEvent $event): void
    {
        $item = $event->getItem();
        $file = $item->file;

        // Check if we need to apply the listener.
        if ($item->getState()->extension() !== 'js'
            || 1 !== preg_match('/\.component\.[a-z]+$/', $file->path)
            || !$item->getState()->isReady()
        ) {
            return;
        }

        $content = $item->getContent();
        $reader  = new FileReader($this->config->cwd());

        $content = preg_replace_callback(
            '/templateUrl\s*:(\s*[\'"`](.*?)[\'"`]\s*)/m',
            function ($match) use ($file, $reader) {
                return 'template: ' . json_encode($this->getCompiledAssetFor($match[2], $file, $reader));
            },
            $content
        );
        $content = preg_replace_callback('/styleUrls *:(\s*\[[^\]]*?\])/', function ($match) use ($file, $reader) {
            $urls = [];

            if (preg_match_all('/([\'`"])((?:[^\\\\]\\\\\1|.)*?)\1/', $match[1], $inner_matches) > 0) {
                foreach ($inner_matches[2] as $inner_match) {
                    $urls[] = json_encode($this->getCompiledAssetFor($inner_match, $file, $reader));
                }
            }

            return 'styles: [' . implode(', ', $urls) . ']';
        }, $content);

        // Keep the current state, but update the content.
        $item->transition($item->getState()->current(), $content);
    }

    /**
     * @param string          $linked_file
     * @param File            $owning_file
     * @param ReaderInterface $reader
     * @return string
     */
    private function getCompiledAssetFor(string $linked_file, File $owning_file, ReaderInterface $reader): string
    {
        $file_path = $linked_file;

        if ($file_path[0] === '.' && $owning_file->dir !== '.' && !empty($owning_file->dir)) {
            $file_path = $owning_file->dir . '/' . $file_path;
        }

        $target_file = new File(File::clean($file_path));
        $asset       = new Asset($this->finder->all($target_file), $this->pipeline->peek($target_file));

        return $this->pipeline->push(
            $asset->getFiles(),
            $reader
        );
    }
}

<?php
namespace Hostnet\Component\Resolver\EventListener;

use Hostnet\Component\Resolver\Bundler\Asset;
use Hostnet\Component\Resolver\Bundler\Pipeline\ContentPipeline;
use Hostnet\Component\Resolver\FileSystem\FileReader;
use Hostnet\Component\Resolver\ConfigInterface;
use Hostnet\Component\Resolver\Event\AssetEvent;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\ReaderInterface;
use Hostnet\Component\Resolver\Import\Dependency;

class AngularHtmlListener
{
    private $config;
    private $pipeline;
    private $file_reader;

    public function __construct(ConfigInterface $config, ContentPipeline $pipeline)
    {
        $this->config = $config;
        $this->pipeline = $pipeline;
    }

    /**
     * {@inheritdoc}
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

        $content = preg_replace_callback('/templateUrl\s*:(\s*[\'"`](.*?)[\'"`]\s*)/m', function ($match) use ($file, $reader) {
            return 'template: ' . json_encode($this->getCompiledAssetFor($match[2], $file, $reader));
        }, $content);
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

    private function getCompiledAssetFor(string $linked_file, File $owning_file, ReaderInterface $reader): string
    {
        $file_path = $linked_file;

        if ($file_path[0] === '.' && $owning_file->dir !== '.' && !empty($owning_file->dir)) {
            $file_path = $owning_file->dir . '/' . substr($file_path, 1);
        }

        if (!empty($this->config->getSourceRoot())) {
            $file_path = $this->config->getSourceRoot() . '/' . $file_path;
        }

        $target = new Dependency(new File(File::clean($file_path)));
        $asset  = new Asset($target, $this->pipeline->peek($target->getFile()));

        return $this->pipeline->push(
            $asset->getFiles(),
            $asset->getAssetFile($this->config->getOutputFolder(), $this->config->getSourceRoot()),
            $reader
        );
    }
}

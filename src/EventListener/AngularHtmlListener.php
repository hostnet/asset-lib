<?php
namespace Hostnet\Component\Resolver\EventListener;

use Hostnet\Component\Resolver\Bundler\Asset;
use Hostnet\Component\Resolver\Bundler\Pipeline\ContentPipeline;
use Hostnet\Component\Resolver\FileSystem\FileReader;
use Hostnet\Component\Resolver\ConfigInterface;
use Hostnet\Component\Resolver\Event\AssetEvent;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\Dependency;

class AngularHtmlListener
{
    private $config;
    private $pipeline;

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

        $content     = $item->getContent();
        $file_reader = new FileReader($this->config->cwd());

        $content = preg_replace_callback('/templateUrl\s*:(\s*[\'"`](.*?)[\'"`]\s*)/m', function ($match) use ($file, $file_reader) {
            $file_path = $match[2];

            if ($file_path[0] === '.') {
                $file_path = substr($file->dir, strpos($file->dir, '/') + 1) . substr($file_path, 1);
            }

            $target = new Dependency(new File($this->config->getSourceRoot() . '/' . $file_path));
            $asset  = new Asset($target, $this->pipeline->peek($target->getFile()));

            $html = $this->pipeline->push(
                $asset->getFiles(),
                $asset->getAssetFile($this->config->getOutputFolder(), $this->config->getSourceRoot()),
                $file_reader
            );

            return 'template: ' . json_encode($html);
        }, $content);
        $content = preg_replace_callback('/styleUrls *:(\s*\[[^\]]*?\])/', function ($match) use ($file, $file_reader) {
            $urls = [];

            if (preg_match_all('/([\'`"])((?:[^\\\\]\\\\\1|.)*?)\1/', $match[1], $inner_matches) > 0) {
                foreach ($inner_matches[2] as $inner_match) {
                    $file_path = $inner_match;

                    if ($file_path[0] === '.') {
                        $file_path = substr($file->dir, strpos($file->dir, '/') + 1) . substr($file_path, 1);
                    }

                    $target = new Dependency(new File($this->config->getSourceRoot() . '/' . $file_path));
                    $asset  = new Asset($target, $this->pipeline->peek($target->getFile()));

                    $css = $this->pipeline->push(
                        $asset->getFiles(),
                        $asset->getAssetFile($this->config->getOutputFolder(), $this->config->getSourceRoot()),
                        $file_reader
                    );

                    $urls[] = json_encode($css);
                }
            }

            return 'styles: [' . implode(', ', $urls) . ']';
        }, $content);

        // Keep the current state, but update the content.
        $item->transition($item->getState()->current(), $content);
    }
}

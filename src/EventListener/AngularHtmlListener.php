<?php
namespace Hostnet\Component\Resolver\EventListener;

use Hostnet\Component\Resolver\ConfigInterface;
use Hostnet\Component\Resolver\Event\AssetEvent;

class AngularHtmlListener
{
    private $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
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

        $content = preg_replace_callback('/templateUrl\s*:(\s*[\'"`](.*?)[\'"`]\s*)/m', function ($match) use ($file) {
            $file_path = $match[2];

            if ($file_path[0] === '.') {
                $file_path = substr($file->dir, strpos($file->dir, '/') + 1) . substr($file_path, 1);
            }

            return 'templateUrl: "' . $this->config->getOutputFolder() . '/' . $file_path . '"';
        }, $content);
        $content = preg_replace_callback('/styleUrls *:(\s*\[[^\]]*?\])/', function ($match) use ($file) {
            $urls = [];

            if (preg_match_all('/([\'`"])((?:[^\\\\]\\\\\1|.)*?)\1/', $match[1], $inner_matches) > 0) {
                foreach ($inner_matches[2] as $inner_match) {
                    $file_path = $inner_match;

                    if ($file_path[0] === '.') {
                        $file_path = substr($file->dir, strpos($file->dir, '/') + 1) . substr($file_path, 1);
                    }

                    $file_path = dirname($file_path) . '/' . substr(basename($file_path), 0, strrpos(basename($file_path), '.')) . '.css';

                    $urls[] = '"' . $this->config->getOutputFolder() . '/' . $file_path . '"';
                }
            }

            return 'styleUrls: [' . implode(', ', $urls) . ']';
        }, $content);

        // Keep the current state, but update the content.
        $item->transition($item->getState()->current(), $content);
    }
}

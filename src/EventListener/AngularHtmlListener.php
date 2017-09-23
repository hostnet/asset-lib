<?php
namespace Hostnet\Component\Resolver\EventListener;

use Hostnet\Component\Resolver\Event\AssetEvent;

class AngularHtmlListener
{
    /**
     * {@inheritdoc}
     */
    public function onPostTranspile(AssetEvent $event): void
    {
        $item = $event->getItem();
        $file = $item->file;

        // Check if we need to apply the listener.
        if ($item->getState()->extension() !== 'js' || 1 !== preg_match('/\.component\.js$/', $file->path)) {
            return;
        }

        $content = $item->getContent();

        $content = preg_replace_callback('/templateUrl\s*:(\s*[\'"`](.*?)[\'"`]\s*)/m', function ($match) use ($file, $output_dir) {
            $file_path = $match[2];

            if ($file_path[0] === '.') {
                $file_path = substr($file->dir, strpos($file->dir, '/') + 1) . substr($file_path, 1);
            }

            return 'templateUrl: "' . $output_dir . '/' . $file_path . '"';
        }, $content);
        $content = preg_replace_callback('/styleUrls *:(\s*\[[^\]]*?\])/', function ($match) use ($file, $output_dir) {
            $urls = [];

            if (preg_match_all('/([\'`"])((?:[^\\\\]\\\\\1|.)*?)\1/', $match[1], $inner_matches) > 0) {
                foreach ($inner_matches[2] as $inner_match) {
                    $file_path = $inner_match;

                    if ($file_path[0] === '.') {
                        $file_path = substr($file->dir, strpos($file->dir, '/') + 1) . substr($file_path, 1);
                    }

                    $file_path = dirname($file_path) . '/' . substr(basename($file_path), 0, strrpos(basename($file_path), '.')) . '.css';

                    $urls[] = '"' . $output_dir . '/' . $file_path . '"';
                }
            }

            return 'styleUrls: [' . implode(', ', $urls) . ']';
        }, $content);

        // Keep the current state, but update the content.
        $item->transition($item->getState()->current(), $content);
    }
}

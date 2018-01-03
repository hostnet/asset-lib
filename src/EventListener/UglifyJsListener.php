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
use Hostnet\Component\Resolver\FileSystem\StringReader;

/**
 * The UglifyJS listener will push all JS content through the UglifyJs
 * minimizer. This will reduce the total file size.
 *
 * @see https://github.com/mishoo/UglifyJS
 */
class UglifyJsListener
{
    private $runner;

    public function __construct(RunnerInterface $runner)
    {
        $this->runner = $runner;
    }

    /**
     * @param FileEvent $event
     */
    public function onPreWrite(FileEvent $event): void
    {
        $file = $event->getFile();

        // Check if we need to apply the listener.
        if ($file->extension !== 'js') {
            return;
        }

        // Create an item for the file.
        $item = new ContentItem($file, $file->getName(), new StringReader($event->getContent()));

        $event->setContent($this->runner->execute(RunnerType::UGLIFY, $item));
    }
}

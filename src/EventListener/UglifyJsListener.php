<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\EventListener;

use Hostnet\Component\Resolver\Bundler\Runner\RunnerInterface;
use Hostnet\Component\Resolver\Bundler\Runner\RunnerType;
use Hostnet\Component\Resolver\Event\AssetEvent;

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
     * @param AssetEvent $event
     */
    public function onPreWrite(AssetEvent $event): void
    {
        $item = $event->getItem();

        // Check if we need to apply the listener.
        if ($item->getState()->extension() !== 'js') {
            return;
        }

        // Keep the current state, but update the content.
        $item->transition($item->getState()->current(), $this->runner->execute(RunnerType::UGLIFY, $item));
    }
}

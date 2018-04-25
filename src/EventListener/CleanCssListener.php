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
 * The Clean CSS listener will push all CSS content through the CleanCSS
 * minimizer. This will reduce the total file size.
 *
 * @see https://github.com/jakubpawlowicz/clean-css
 */
class CleanCssListener
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
        if ($item->getState()->extension() !== 'css') {
            return;
        }

        // Keep the current state, but update the content.
        $item->transition($item->getState()->current(), $this->runner->execute(RunnerType::CLEAN_CSS, $item));
    }
}

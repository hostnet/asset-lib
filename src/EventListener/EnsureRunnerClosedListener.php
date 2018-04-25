<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\EventListener;

use Hostnet\Component\Resolver\Bundler\Runner\RunnerInterface;
use Hostnet\Component\Resolver\Bundler\Runner\UnixSocketRunner;
use Hostnet\Component\Resolver\Event\BundleEvent;

class EnsureRunnerClosedListener
{
    private $runner;

    public function __construct(RunnerInterface $runner)
    {
        $this->runner = $runner;
    }

    public function onPostBundle(BundleEvent $event): void
    {
        if (!$this->runner instanceof UnixSocketRunner) {
            return;
        }

        $this->runner->shutdown();
    }
}

<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\EventListener;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\Runner\RunnerInterface;
use Hostnet\Component\Resolver\Bundler\Runner\UnixSocketRunner;
use Hostnet\Component\Resolver\Event\BundleEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\EventListener\EnsureRunnerClosedListener
 */
class EnsureRunnerClosedListenerTest extends TestCase
{
    public function testOnPostBundle()
    {
        $runner                        = $this->prophesize(UnixSocketRunner::class);
        $ensure_runner_closed_listener = new EnsureRunnerClosedListener($runner->reveal());

        $runner->shutdown()->shouldBeCalled();

        $ensure_runner_closed_listener->onPostBundle(new BundleEvent());
    }

    public function testOnPostBundleOtherUnix()
    {
        $runner = new class implements RunnerInterface
        {
            public function execute(string $type, ContentItem $item): string
            {
            }

            public function shutdown(): void
            {
                throw new \RuntimeException();
            }
        };
        $ensure_runner_closed_listener = new EnsureRunnerClosedListener($runner);
        $ensure_runner_closed_listener->onPostBundle(new BundleEvent());

        $this->addToAssertionCount(1);
    }
}

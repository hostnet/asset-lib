<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Bundle events are thrown before and after a the bundler starts processing assets.
 */
class BundleEvent extends Event
{
}

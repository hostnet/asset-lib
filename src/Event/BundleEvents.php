<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Event;

/**
 * Contains all the events thrown during asset compilation.
 */
final class BundleEvents
{
    /**
     * Event triggered just before the bundler starts bundeling assets.
     *
     * @Event("Hostnet\Component\Resolver\Event\BundleEvent")
     *
     * @var string
     */
    public const PRE_BUNDLE = 'bundler.pre_bundle';

    /**
     * Event triggered just after the transpiling of an asset.
     *
     * @Event("Hostnet\Component\Resolver\Event\BundleEvent")
     *
     * @var string
     */
    public const POST_BUNDLE = 'bundler.pre_bundle';
}

<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Event;

/**
 * Contains all the events thrown during asset compilation.
 */
final class AssetEvents
{
    /**
     * Event triggered just before the transpiling of an asset.
     *
     * @Event("Hostnet\Component\Resolver\Event\AssetEvent")
     *
     * @var string
     */
    public const PRE_PROCESS = 'asset.pre_process';

    /**
     * Event triggered just after the transpiling of an asset.
     *
     * @Event("Hostnet\Component\Resolver\Event\AssetEvent")
     *
     * @var string
     */
    public const POST_PROCESS = 'asset.post_process';

    /**
     * Event triggered just after the asset is marked as ready.
     *
     * @Event("Hostnet\Component\Resolver\Event\AssetEvent")
     *
     * @var string
     */
    public const READY = 'asset.ready';
}

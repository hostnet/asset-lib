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
    public const PRE_TRANSPILE = 'asset.pre_transpile';

    /**
     * Event triggered just after the transpiling of an asset.
     *
     * @Event("Hostnet\Component\Resolver\Event\AssetEvent")
     *
     * @var string
     */
    public const POST_TRANSPILE = 'asset.post_transpile';

    /**
     * Event triggered just before wrapping of javascript modules.
     *
     * @Event("Hostnet\Component\Resolver\Event\AssetEvent")
     *
     * @var string
     */
    public const PRE_WRAP = 'asset.pre_wrap';

    /**
     * Event triggered just after wrapping of javascript modules.
     *
     * @Event("Hostnet\Component\Resolver\Event\AssetEvent")
     *
     * @var string
     */
    public const POST_WRAP = 'asset.post_wrap';

    /**
     * Event triggered just before writing the file to disk.
     *
     * @Event("Hostnet\Component\Resolver\Event\AssetEvent")
     *
     * @var string
     */
    public const PRE_WRITE = 'asset.pre_write';

    /**
     * Event triggered just after writing the file to disk.
     *
     * Note: changing the content does not effect the file anymore since it has
     * already been written to disk.
     *
     * @Event("Hostnet\Component\Resolver\Event\AssetEvent")
     *
     * @var string
     */
    public const POST_WRITE = 'asset.post_write';
}

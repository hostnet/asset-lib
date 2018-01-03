<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Event;

/**
 * Contains all the events thrown during IO operations.
 */
final class FileEvents
{
    /**
     * Event triggered just before a file is written to disk.
     *
     * @Event("Hostnet\Component\Resolver\Event\FileEvent")
     *
     * @var string
     */
    public const PRE_WRITE = 'file.pre_bundle';

    /**
     * Event triggered just after a file is written to disk.
     *
     * @Event("Hostnet\Component\Resolver\Event\FileEvent")
     *
     * @var string
     */
    public const POST_WRITE = 'file.pre_bundle';
}

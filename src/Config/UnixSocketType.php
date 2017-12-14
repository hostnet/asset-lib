<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Config;

final class UnixSocketType
{
    /**
     * Always use the unix socket.
     */
    public const ALWAYS = 'always';

    /**
     * Only run the socket per process, close it again once the bundler is
     * done.
     */
    public const PRE_PROCESS = 'per_process';

    /**
     * Do not use the socket.
     */
    public const DISABLED = 'disabled';
}

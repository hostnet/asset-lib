<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler\Runner\Exception;

use Hostnet\Component\Resolver\Bundler\Runner\UnixSocketRunner;

/**
 * This exception is thrown whenever the unix socket communication takes too
 * long.
 *
 * @see UnixSocketRunner
 */
class TimeoutException extends \RuntimeException
{
    public function __construct(string $what, int $timeout)
    {
        parent::__construct(sprintf(
            '%s reached timeout of %d seconds',
            $what,
            $timeout
        ));
    }
}

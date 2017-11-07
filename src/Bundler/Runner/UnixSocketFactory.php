<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler\Runner;

/**
 * Factory that creates unix sockets.
 *
 * Created so the creation of a unix socket can differ in a unit-test.
 */
class UnixSocketFactory
{
    public function make(): UnixSocket
    {
        return new UnixSocket();
    }
}

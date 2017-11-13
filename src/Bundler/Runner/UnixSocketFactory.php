<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler\Runner;

use Psr\Log\LoggerInterface;

/**
 * Factory that creates unix sockets.
 *
 * Created so the creation of a unix socket can differ in a unit-test.
 */
class UnixSocketFactory
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function make(): UnixSocket
    {
        return new UnixSocket($this->logger);
    }
}

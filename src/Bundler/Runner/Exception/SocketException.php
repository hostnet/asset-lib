<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler\Runner\Exception;

use Hostnet\Component\Resolver\Bundler\Runner\UnixSocketRunner;

/**
 * This exception is thrown whenever something goes wrong with the unix socket
 * communication.
 *
 * @see UnixSocketRunner
 */
class SocketException extends \RuntimeException
{
}

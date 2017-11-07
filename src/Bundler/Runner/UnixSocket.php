<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler\Runner;

use Hostnet\Component\Resolver\Bundler\Runner\Exception\SocketException;

/**
 * Small abstraction around the socket functions of php
 *
 * It adds
 * - type hints
 * - exceptions on failures (instead of return false)
 * - and the ability to unit-test a socket using class
 */
class UnixSocket
{
    private $socket;

    public function __construct()
    {
        $this->socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
    }

    public function connect(string $address): void
    {
        if (! @socket_connect($this->socket, $address)) {
            throw new SocketException('Problem connecting to the socket');
        }
    }

    public function send(string $to_send, int $bytes): void
    {
        if (! @socket_send($this->socket, $to_send, $bytes, 0)) {
            throw new SocketException('Problem sending to the socket');
        }
    }

    public function receive(int $bytes): string
    {
        $buffer = '';
        if (false === @socket_recv($this->socket, $buffer, $bytes, MSG_WAITALL)) {
            throw new SocketException('Problem receiving from the socket');
        }

        return $buffer;
    }

    public function close(): void
    {
        socket_close($this->socket);
    }
}

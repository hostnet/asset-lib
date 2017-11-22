<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler\Runner;

use Hostnet\Component\Resolver\Bundler\Runner\Exception\SocketException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

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
    private $logger;
    private $socket;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 30, 'usec' => 0]);
    }

    public function connect(string $address): void
    {
        if (!@socket_connect($this->socket, $address)) {
            throw new SocketException('[UnixSocket] Connecting exception ' . $this->getSocketError());
        }
    }

    public function send(string $to_send, int $bytes): void
    {
        $this->logger->debug('[UnixSocket] Sending ' . $bytes);
        if (!@socket_send($this->socket, $to_send, $bytes, 0)) {
            throw new SocketException('[UnixSocket] Sending exception ' . $this->getSocketError());
        }
        $this->logger->debug('[UnixSocket] Sending done');
    }

    public function receive(int $bytes): string
    {
        if ($bytes === 0) {
            return '';
        }

        $buffer = '';
        $length = 0;

        while ($bytes > $length) {
            $this->logger->debug('[UnixSocket] Reading ' . ($bytes - $length) . ' bytes');
            $res = @socket_read($this->socket, $bytes - $length);
            if ($res === false) {
                throw new SocketException('[UnixSocket] Reading exception ' . $this->getSocketError());
            }
            $length += strlen($res);
            $buffer .= $res;
        }

        $this->logger->debug('[UnixSocket] Reading done');

        return $buffer;
    }

    public function close(): void
    {
        socket_close($this->socket);
    }

    private function getSocketError(): string
    {
        return socket_last_error() . ' (' . socket_strerror(socket_last_error()) . ')';
    }
}

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
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 3, 'usec' => 0]);
    }

    public function connect(string $address): void
    {
        if (!@socket_connect($this->socket, $address)) {
            $this->logger->debug('[UnixSocket] Connection exception');
            throw new SocketException('Problem connecting to the socket');
        }
    }

    public function send(string $to_send, int $bytes): void
    {
        $this->logger->debug('[UnixSocket] Sending ' . $bytes);
        if (!@socket_send($this->socket, $to_send, $bytes, 0)) {
            $this->logger->debug('[UnixSocket] Sending exception');
            throw new SocketException('Problem sending to the socket');
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
            $care = '';
            $res  = @socket_recv($this->socket, $care, 1, MSG_PEEK);

            $this->logger->debug('[UnixSocket] Reading ' . ($bytes - $length) . ' bytes');
            $res = @socket_read($this->socket, $bytes - $length);
            if ($res === false) {
                $this->logger->debug(
                    '[UnixSocket] Reading exception '
                    . socket_last_error() . ' (' . socket_strerror(socket_last_error()) . ')'
                );
                throw new SocketException('Problem receiving from the socket');
            }
            $length += strlen($res);
            $buffer .= $res;
        }

        $this->logger->debug('[UnixSocket] Read');

        return $buffer;
    }

    public function close(): void
    {
        socket_close($this->socket);
    }
}

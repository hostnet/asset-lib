<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler\Runner;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\Runner\Exception\SocketException;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\File;

class UnixSocketRunner implements RunnerInterface
{
    private $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    public function execute(string $type, ContentItem $item): string
    {
        $file_name = File::makeAbsolutePath($item->file->path, $this->config->getProjectRoot());
        $start     = microtime(true);
        $response  = '';

        while (true) {
            if (microtime(true) - $start > 30) {
                throw new \RuntimeException('Timeout of 30s');
            }

            $socket = socket_create(AF_UNIX, SOCK_STREAM, 0);

            // Ensure we have a process running
            // TODO $this->config->getCacheDir()
            if (!file_exists('/tmp/build')) {
                $this->startBuildProcess();
                usleep(500000);
                continue;
            }

            if (!@socket_connect($socket, '/tmp/build')) {
                usleep(100000);
                continue;
            }

            try {
                $response = $this->sendMessage($socket, $type, $file_name, $item->getContent());
                break;
            } catch (SocketException $e) {
                usleep(100000);
                continue;
            }
        }

        socket_close($socket);

        return $response;
    }

    private function sendMessage($socket, $type, $file_name, $msg)
    {
        /*
         * The request header is 7 bytes:
         * - 4 bytes File length, unsigned long (32 bit, little endian byte order)
         * - 3 bytes for a three character system name of the operation to perform
         *
         * After that the file is sent.
         */
        if (strlen($type) !== 3) {
            throw new \Exception('Type is always three bytes');
        }
        $msg_length = strlen($msg);

        $to_send = pack('V', $msg_length) . $type . $file_name . "\0" . $msg;
        $length  = 4 + 3 + strlen($file_name) + 1 + $msg_length;
        if (false === @socket_send($socket, $to_send, $length, 0)) {
            throw new SocketException('Problem sending to the socket');
        }

        /**
         * The response header is
         * - 4 bytes Resulting file length, unsigned long (32 bit, little endian byte order)
         * - 1 byte boolean flags
         *
         * $flags & 1 => Success
         * $flags & 2 => I just killed myself
         * (The other 6 flags left open)
         *
         * After that the result is sent.
         */
        $buffer = null;
        if (false === @socket_recv($socket, $buffer, 4, MSG_WAITALL)) {
            throw new SocketException('Problem receiving from the socket');
        }
        $length = unpack('Vlength', $buffer)['length'];
        if (false === @socket_recv($socket, $buffer, 1, MSG_WAITALL)) {
            throw new SocketException('Problem receiving from the socket');
        }
        $flags   = ord($buffer);
        $success = $flags & 1;
        $restart = $flags & 2;

        if (false === @socket_recv($socket, $buffer, $length, MSG_WAITALL)) {
            throw new SocketException('Problem receiving from the socket');
        }

        if ($success) {
            return $buffer;
        }

        if ($restart) {
            $this->startBuildProcess();
        }

        throw new \Exception(sprintf('Error with %s compile: %s', $type, $buffer));
    }

    private function startBuildProcess()
    {
        $node_js = $this->config->getNodeJsExecutable();
        $cmd     = sprintf(
            'nohup %s %s > /dev/null &',
            $node_js->getBinary(),
            implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'Resources', 'build.js'])
        );
        `$cmd`;
    }

    private function get
}

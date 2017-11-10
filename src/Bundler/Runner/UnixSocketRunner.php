<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler\Runner;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\Runner\Exception\SocketException;
use Hostnet\Component\Resolver\Bundler\Runner\Exception\TimeoutException;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\File;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * This runs JavaScript commands using a unix socket.
 *
 * This way allows the javascript commands to run in the background, thus
 * preventing the startup time usually related to starting node commands.
 *
 * Consider using this if you're running a unix based system.
 *
 * You can enable this via the configuration.
 *
 * This assumes the sockets extension is enabled.
 * @see http://php.net/manual/en/book.sockets.php
 */
class UnixSocketRunner implements RunnerInterface
{
    private $config;
    private $socket_location;
    private $factory;
    private $logger;
    private $start_timeout;
    private $small_timeout;

    public function __construct(
        ConfigInterface $config,
        UnixSocketFactory $factory,
        LoggerInterface $logger = null,
        int $start_timeout = 5000000,
        int $small_timeout = 100000
    ) {
        $this->config          = $config;
        $this->socket_location = $config->getCacheDir() . '/asset-lib.socket';
        $this->factory         = $factory;
        $this->logger          = $logger ?? new NullLogger();
        $this->start_timeout   = $start_timeout;
        $this->small_timeout   = $small_timeout;
    }

    public function execute(string $type, ContentItem $item): string
    {
        $this->logger->debug('[UnixSocketRunner] Executing '.$type.' for ' . $item->file->path);
        $file_name = File::makeAbsolutePath($item->file->path, $this->config->getProjectRoot());
        $start     = microtime(true);
        $response  = '';

        while (true) {
            if (microtime(true) - $start > 10) {
                throw new TimeoutException('Socket communication', 10);
            }

            $socket = $this->factory->make();

            try {
                // Ensure we have a process running
                if (!file_exists($this->socket_location)) {
                    $this->startBuildProcess();
                    usleep($this->start_timeout);
                    continue;
                }

                try {
                    $socket->connect($this->socket_location);
                } catch (SocketException $e) {
                    usleep($this->small_timeout);
                    continue;
                }

                try {
                    $response = $this->sendMessage($socket, $type, $file_name, $item->getContent());
                    break;
                } catch (SocketException $e) {
                    usleep($this->small_timeout);
                    continue;
                }
            } finally {
                $socket->close();
            }
        }

        return $response;
    }

    private function sendMessage(UnixSocket $socket, string $type, string $file_name, string $msg): string
    {
        /*
         * The request
         * - 3 bytes for a three character system name of the operation to perform
         * - 4 bytes File name length, unsigned long (32 bit, little endian byte order)
         * - <length> bytes of file name
         * - 4 bytes File length, unsigned long (32 bit, little endian byte order)
         * - <length> bytes of file
         */
        if (strlen($type) !== 3) {
            throw new \DomainException('Type is always three bytes');
        }
        $file_name_length = strlen($file_name);
        $msg_length       = strlen($msg);

        $to_send = $type . pack('V', $file_name_length) . $file_name . pack('V', $msg_length) . $msg;
        $length  = 3 + 4 + $file_name_length + 4 + $msg_length;

        $socket->send($to_send, $length);

        /**
         * The response header is
         * - 1 byte boolean flags
         * - 4 bytes Resulting file length, unsigned long (32 bit, little endian byte order)
         *
         * $flags & 1 => Success
         * $flags & 2 => I just killed myself
         * (The other 6 flags left open)
         *
         * After that the result is sent.
         */
        $buffer  = $socket->receive(1);
        $flags   = ord($buffer);
        $success = $flags & 1;
        $restart = $flags & 2;

        $buffer = $socket->receive(4);
        $length = unpack('Vlength', $buffer)['length'];

        $buffer = $socket->receive($length);

        if ($success) {
            return $buffer;
        }

        if ($restart) {
            $this->startBuildProcess();
        }

        throw new \DomainException(sprintf('Error with %s compile: %s', $type, $buffer));
    }

    private function startBuildProcess()
    {
        $this->logger->debug('buuuuuurp');
        if (!is_dir($this->config->getCacheDir())) {
            mkdir($this->config->getCacheDir(), 0777, true);
        }

        $node_js = $this->config->getNodeJsExecutable();
        $cmd     = sprintf(
            'nohup %s %s %s < /dev/null > %s 2>&1 &',
            escapeshellarg($node_js->getBinary()),
            escapeshellarg(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'Resources', 'build.js'])),
            escapeshellarg($this->socket_location),
            escapeshellarg($this->config->getCacheDir() . '/asset-lib.log')
        );

        $this->logger->debug($cmd);
        `$cmd`;
    }
}

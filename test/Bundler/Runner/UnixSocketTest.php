<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler\Runner;

use Hostnet\Component\Resolver\Bundler\Runner\Exception\SocketException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Bundler\Runner\UnixSocket
 */
class UnixSocketTest extends TestCase
{
    /**
     * @var UnixSocket
     */
    private $unix_socket;

    protected function setUp()
    {
        if (! function_exists('socket_create')) {
            self::markTestSkipped('Not available without socket extension');
        }
        $this->unix_socket = new UnixSocket();
    }

    public function testConnect()
    {
        $this->expectException(SocketException::class);
        $this->unix_socket->connect('bla');
    }

    public function testSend()
    {
        $this->expectException(SocketException::class);
        $this->unix_socket->send('bla', 3);
    }

    public function testReceive()
    {
        $this->expectException(SocketException::class);
        $this->unix_socket->receive(1);
    }

    public function testReceiveZeroBytes()
    {
        self::assertSame('', $this->unix_socket->receive(0));
    }

    public function testClose()
    {
        $this->unix_socket->close();
        self::assertTrue(true);
    }
}

<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler\Runner;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\ReaderInterface;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @covers \Hostnet\Component\Resolver\Bundler\Runner\UnixSocketRunner
 */
class UnixSocketRunnerTest extends TestCase
{
    private $cache;
    private $config;
    private $factory;
    private $socket;

    /**
     * @var UnixSocketRunner
     */
    private $unix_socket_runner;

    protected function setUp()
    {
        // Fake a cache directory with a socket file in it
        // So the program does not try to start the build process
        $this->cache = vfsStream::setup('unixSocketCacheDir');
        $this->cache->addChild(new vfsStreamFile('asset-lib.socket'));

        $this->config = $this->prophesize(ConfigInterface::class);
        $this->config->getCacheDir()->willReturn(vfsStream::url('unixSocketCacheDir'));
        $this->config->getProjectRoot()->willReturn('/dir');
        $this->config->getNodeJsExecutable()->willReturn(new Executable('echo', 'node_modules'));
        $this->config->getLogger()->willReturn(new NullLogger());
        $this->socket  = $this->prophesize(UnixSocket::class);
        $this->factory = $this->prophesize(UnixSocketFactory::class);
        $this->factory->make()->willReturn($this->socket);

        $this->unix_socket_runner = new UnixSocketRunner(
            $this->config->reveal(),
            $this->factory->reveal()
        );
    }

    public function testExecuteTypeTooLong()
    {
        $file   = new File('a.js');
        $reader = $this->prophesize(ReaderInterface::class);
        $reader->read($file)->willReturn('awesome content');
        $item = new ContentItem($file, 'a', $reader->reveal());

        $this->expectException(\DomainException::class);
        $this->unix_socket_runner->execute('NASA', $item);
    }

    public function testExecute()
    {
        $file   = new File('a.js');
        $reader = $this->prophesize(ReaderInterface::class);
        $reader->read($file)->willReturn('awesome content');
        $item = new ContentItem($file, 'a', $reader->reveal());

        $this->setUpProtocol(true, 'awesome response');

        self::assertSame(
            'awesome response',
            $this->unix_socket_runner->execute('UGL', $item)
        );
    }

    public function testExecuteFailed()
    {
        $file   = new File('a.js');
        $reader = $this->prophesize(ReaderInterface::class);
        $reader->read($file)->willReturn('awesome content');
        $item = new ContentItem($file, 'a', $reader->reveal());

        $this->setUpProtocol(false, 'error response');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('error response');

        $this->unix_socket_runner->execute('UGL', $item);
    }

    private function setUpProtocol(bool $success, string $message)
    {
        $url = vfsStream::url('unixSocketCacheDir') . '/asset-lib.socket';
        $this->socket->connect($url)->will(function () use ($success, $message) {
            $file     = '/dir/a.js';
            $content  = 'awesome content';
            $expected =
                'UGL' .
                pack('V', strlen($file)) .
                $file .
                pack('V', strlen($content)) .
                $content;

            $this->send($expected, strlen($expected))->will(function () use ($success, $message) {
                $this->receive(1)->will(function () use ($success, $message) {
                    $this->receive(4)->will(function () use ($message) {
                        $this->receive(16)->will(function () use ($message) {
                            $this->close()->shouldBeCalled();
                            return $message;
                        });
                        return pack('V', 16);
                    });
                    return $success ? 1 : 0;
                });
            });
        });
    }
}

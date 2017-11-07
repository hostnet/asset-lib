<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler\Runner;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Bundler\Runner\UnixSocketFactory
 */
class UnixSocketFactoryTest extends TestCase
{
    public function testMake()
    {
        $factory = new UnixSocketFactory();

        if (! function_exists('socket_create')) {
            self::markTestSkipped('Not available without socket extension');
        }
        self::assertInstanceOf(UnixSocket::class, $factory->make());
    }
}

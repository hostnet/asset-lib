<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler\Runner\Exception;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Bundler\Runner\Exception\SocketException
 */
class SocketExceptionTest extends TestCase
{
    public function testConstruct()
    {
        self::assertSame('bla', (new SocketException('bla'))->getMessage());
    }
}

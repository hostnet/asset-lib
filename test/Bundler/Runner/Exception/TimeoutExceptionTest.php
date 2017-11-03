<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler\Runner\Exception;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Bundler\Runner\Exception\TimeoutException
 */
class TimeoutExceptionTest extends TestCase
{
    public function testConstruct()
    {
        $exception = new TimeoutException('foobarbaz', 423);
        self::assertContains('foobarbaz', $exception->getMessage());
        self::assertContains('423', $exception->getMessage());
    }
}

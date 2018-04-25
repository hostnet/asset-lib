<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Bundler\TranspileException
 */
class TranspileExceptionTest extends TestCase
{
    public function testGeneric()
    {
        $e = new TranspileException('foobar', 'barbaz', $previous = new \RuntimeException());

        self::assertSame($previous, $e->getPrevious());
        self::assertSame('foobar Error: barbaz', $e->getMessage());
        self::assertSame('barbaz', $e->getErrorOutput());
    }
}

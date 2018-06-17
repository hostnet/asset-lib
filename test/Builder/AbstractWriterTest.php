<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Builder\AbstractWriter
 */
class AbstractWriterTest extends TestCase
{
    public function testGeneric(): void
    {
        $writer = new MockWriter();

        self::assertSame(serialize([
            \get_class($writer),
            $writer->acceptedExtension(),
            $writer->getJsModule(),
        ]), $writer->getHash());
    }
}

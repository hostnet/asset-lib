<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder\Writer;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Builder\Writer\BrotliFileWriter
 */
class BrotliFileWriterTest extends TestCase
{
    public function testGeneric(): void
    {
        $step = new BrotliFileWriter();

        self::assertSame('*', $step->acceptedExtension());
        self::assertStringContainsString('js/writers/brotli.js', $step->getJsModule());
    }
}

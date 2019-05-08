<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder\Writer;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Builder\Writer\GzipFileWriter
 */
class GzipFileWriterTest extends TestCase
{
    public function testGeneric(): void
    {
        $step = new GzipFileWriter();

        self::assertSame('*', $step->acceptedExtension());
        self::assertStringContainsString('js/writers/gz.js', $step->getJsModule());
    }
}

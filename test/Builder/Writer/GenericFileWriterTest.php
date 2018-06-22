<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder\Writer;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Builder\Writer\GenericFileWriter
 */
class GenericFileWriterTest extends TestCase
{
    public function testGeneric(): void
    {
        $step = new GenericFileWriter();

        self::assertSame('*', $step->acceptedExtension());
        self::assertContains('js/writers/generic.js', $step->getJsModule());
    }
}

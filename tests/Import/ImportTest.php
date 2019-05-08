<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import;

use Hostnet\Component\Resolver\File;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Import\Import
 */
class ImportTest extends TestCase
{
    public function testGeneric(): void
    {
        $file = new File('foo.js');

        $import = new Import('foo', $file, true);

        self::assertSame('foo', $import->getAs());
        self::assertSame($file, $import->getImportedFile());
        self::assertTrue($import->isVirtual());
    }
}

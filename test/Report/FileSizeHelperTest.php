<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Report;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Report\FileSizeHelper
 */
class FileSizeHelperTest extends TestCase
{
    /**
     * @dataProvider sizeProvider
     */
    public function testSize(string $expected, int $size): void
    {
        self::assertSame($expected, FileSizeHelper::size($size));
    }

    public function sizeProvider()
    {
        return [
            ['N/A', -1],
            ['0 b', 0],
            ['1 b', 1],
            ['10 b', 10],
            ['1023 b', 1023],
            ['1 kb', 1024],
            ['53 kb', 54457],
            ['1024 kb', 1048575],
            ['1 mb', 1048576],
            ['1024 mb', 1073741823],
            ['1 gb', 1073741824],
            ['1024 gb', 1099511627775]
        ];
    }

    /**
     * @expectedException \LogicException
     */
    public function testSizeMax(): void
    {
        FileSizeHelper::size(1099511627776);
    }
}

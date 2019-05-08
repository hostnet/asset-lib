<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Module
 */
class ModuleTest extends TestCase
{
    public function testGeneric(): void
    {
        $file = new Module('foo/bar', 'node_modules/foo/bar/index.js');

        self::assertEquals('node_modules/foo/bar', $file->dir);
        self::assertEquals('js', $file->extension);
        self::assertEquals('foo/bar', $file->getName());
        self::assertEquals('foo/bar', $file->getParentName());
        self::assertEquals('index', $file->getBaseName());
        self::assertTrue($file->equals(new File('node_modules/foo/bar/index.js')));
        self::assertFalse($file->equals(new File('someOtherFile.php')));
    }

    /**
     * @dataProvider getParentNameProvider
     */
    public function testGetParentName(string $expected, string $name, string $path): void
    {
        self::assertEquals($expected, (new Module($name, $path))->getParentName());
    }

    public function getParentNameProvider(): array
    {
        return [
            ['', 'foo', 'foo/index.js'],
            ['', 'bar.js', 'bar.js'],
            ['foo/bar', 'foo/bar', 'node_modules/foo/bar/index.js'],
            ['foo', 'foo', 'node_modules/foo/index.js'],
            ['foo/dist', 'foo', 'node_modules/foo/dist/index.js'],
            ['rxjs/internal', 'rxjs/internal/Rx', 'node_modules/rxjs/internal/Rx.js'],
        ];
    }
}

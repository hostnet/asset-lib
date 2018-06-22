<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Builder\ExtensionMap
 */
class ExtensionMapTest extends TestCase
{
    public function testGetResultingExtension(): void
    {
        $mapping = new ExtensionMap(['.foo' => '.bar', '.js' => '.js']);

        self::assertSame('.bar', $mapping->getResultingExtension('.foo'));
        self::assertSame('.js', $mapping->getResultingExtension('.js'));
    }

    public function testGetResultingExtensionUnknownType(): void
    {
        $mapping = new ExtensionMap([]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot find resulting extension for ".js".');
        self::assertSame('.js', $mapping->getResultingExtension('.js'));
    }
}

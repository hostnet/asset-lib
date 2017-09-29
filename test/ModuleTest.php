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
    public function testGeneric()
    {
        $file = new Module('foo/bar', __FILE__);

        self::assertEquals(__DIR__, $file->dir);
        self::assertEquals('php', $file->extension);
        self::assertEquals('foo/bar', $file->getName());
        self::assertEquals('foo', $file->getParentName());
        self::assertEquals('.', (new Module('foo', __FILE__))->getParentName());
        self::assertEquals('ModuleTest', $file->getBaseName());
        self::assertTrue($file->equals(new File(__FILE__)));
        self::assertFalse($file->equals(new File('someOtherFile.php')));
    }
}

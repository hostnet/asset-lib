<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\File
 */
class FileTest extends TestCase
{
    public function testGeneric()
    {
        $file = new File(__FILE__);

        self::assertEquals(__DIR__, $file->dir);
        self::assertEquals('php', $file->extension);
        self::assertEquals(__FILE__, $file->getName());
        self::assertEquals('FileTest', $file->getBaseName());
        self::assertTrue($file->equals(new File(__FILE__)));
        self::assertFalse($file->equals(new File('someOtherFile.php')));

        self::assertEquals(__FILE__, File::clean(__FILE__));
        self::assertEquals(__DIR__, File::clean(__DIR__));
        self::assertEquals(__FILE__, File::clean(__DIR__ . '/../test/FileTest.php'));
        self::assertEquals(__FILE__, File::clean(__DIR__ . '/./FileTest.php'));
        self::assertTrue(File::isAbsolutePath(__DIR__));
        self::assertFalse(File::isAbsolutePath('some/other/path'));
        self::assertFalse(File::isAbsolutePath('./some/other/path'));
        self::assertFalse(File::isAbsolutePath('../some/other/path'));
    }
}

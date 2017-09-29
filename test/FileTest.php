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

        self::assertEquals('/some/file.php', File::clean('/some/file.php'));
        self::assertEquals('/some/dir', File::clean('/some/dir'));
        self::assertEquals('/some/test/FileTest.php', File::clean('/some/dir/../test/FileTest.php'));
        self::assertEquals('/some/dir/FileTest.php', File::clean('/some/dir/./FileTest.php'));

        self::assertTrue(File::isAbsolutePath(__DIR__));
        self::assertFalse(File::isAbsolutePath('some/other/path'));
        self::assertFalse(File::isAbsolutePath('./some/other/path'));
        self::assertFalse(File::isAbsolutePath('../some/other/path'));
    }
}

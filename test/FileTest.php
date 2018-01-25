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

        self::assertEquals(File::clean(__FILE__), File::makeAbsolutePath(__FILE__, __DIR__));
        self::assertEquals(File::clean(__FILE__), File::makeAbsolutePath(basename(__FILE__), __DIR__));
        self::assertEquals(
            File::clean(__DIR__ . '/../some/other/path'),
            File::makeAbsolutePath('../some/other/path', __DIR__)
        );

        $file2 = new File('.htaccess');
        self::assertEquals('.', $file2->dir);
        self::assertEquals('', $file2->extension);
        self::assertEquals('.htaccess', $file2->getName());
        self::assertEquals('.htaccess', $file2->getBaseName());

        $file3 = new File('.foo.bar');
        self::assertEquals('.', $file3->dir);
        self::assertEquals('bar', $file3->extension);
        self::assertEquals('.foo.bar', $file3->getName());
        self::assertEquals('.foo', $file3->getBaseName());
    }
}

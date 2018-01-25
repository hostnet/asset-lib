<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\Dependency;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Bundler\Asset
 */
class AssetTest extends TestCase
{
    public function testGeneric()
    {
        $file = new File(__FILE__);
        $dep1 = new Dependency(new File(__DIR__ . '/some.file'));
        $dep2 = new Dependency(new File(__DIR__ . '/other.file'));

        $dep = new Dependency($file);
        $dep->addChild($dep1);
        $dep->addChild($dep2);

        $asset = new Asset($dep, 'php');

        self::assertSame($file, $asset->getFile());
        self::assertSame([$dep, $dep1, $dep2], $asset->getFiles());
        self::assertSame('foo/bar/AssetTest.php', $asset->getAssetFile('foo/bar', __DIR__)->path);
    }

    public function testNestedUri()
    {
        $file = new File(__DIR__ . '/some/file.css');
        $dep  = new Dependency($file);

        $asset = new Asset($dep, 'css');

        self::assertSame('foo/bar/some/file.css', $asset->getAssetFile('foo/bar', __DIR__)->path);
    }

    public function testInRoot()
    {
        $file = new File('file.css');
        $dep  = new Dependency($file);

        $asset = new Asset($dep, 'css');

        self::assertSame('foo/bar/file.css', $asset->getAssetFile('foo/bar', '')->path);
    }

    public function testWithoutExtension()
    {
        self::assertSame(
            'foo/bar/.test',
            (new Asset(new Dependency(new File('.test')), ''))->getAssetFile('foo/bar', '')->path
        );
        self::assertSame(
            'foo/bar/.foo.bar',
            (new Asset(new Dependency(new File('.foo.bar')), 'bar'))->getAssetFile('foo/bar', '')->path
        );
    }
}

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
 * @covers \Hostnet\Component\Resolver\Bundler\EntryPoint
 */
class EntryPointTest extends TestCase
{
    public function testGeneric()
    {
        $file = new File(__FILE__);
        $dep1 = new Dependency(new File(__DIR__ . '/some.file'));
        $dep2 = new Dependency(new File(__DIR__ . '/other.file'), false, true);
        $dep3 = new Dependency(new File('node_modules/foo'));

        $dep = new Dependency($file);
        $dep->addChild($dep1);
        $dep->addChild($dep2);
        $dep->addChild($dep3);

        $entry_point = new EntryPoint($dep);

        self::assertSame($file, $entry_point->getFile());
        self::assertSame([$dep, $dep1], $entry_point->getBundleFiles());
        self::assertSame([$dep3], $entry_point->getVendorFiles());
        self::assertSame([$dep2->getFile()], $entry_point->getAssetFiles());
        self::assertSame('foo/bar/EntryPointTest.bundle.js', $entry_point->getBundleFile('foo/bar')->path);
        self::assertSame('foo/bar/EntryPointTest.vendor.js', $entry_point->getVendorFile('foo/bar')->path);
    }
}

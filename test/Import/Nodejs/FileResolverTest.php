<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import\Nodejs;

use Hostnet\Component\Resolver\ConfigInterface;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\FileResolverInterface;
use Hostnet\Component\Resolver\Module;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Import\Nodejs\FileResolver
 */
class FileResolverTest extends TestCase
{
    /**
     * @var FileResolverInterface
     */
    private $file_resolver;

    protected function setUp()
    {
        $config = $this->prophesize(ConfigInterface::class);
        $config->cwd()->willReturn(__DIR__ . '/../../fixtures');
        $config->getIncludePaths()->willReturn(['some_other_location']);

        $this->file_resolver = new FileResolver($config->reveal(), ['.js', '.json', '.node']);
    }

    public function testAsRequireFile()
    {
        $parent = new File('node_modules/bar/baz.js');
        $import = $this->file_resolver->asRequire('./foo/hom', $parent);

        self::assertInstanceOf(File::class, $import->getImportedFile());
        self::assertSame('node_modules/bar/foo/hom.js', $import->getImportedFile()->path);
        self::assertSame('node_modules/bar/foo/hom.js', $import->getImportedFile()->getName());
    }

    public function testAsRequireFileFromModule()
    {
        $parent = new Module('bar/baz', 'node_modules/bar/baz.js');
        $import = $this->file_resolver->asRequire('./foo/hom', $parent);

        self::assertInstanceOf(Module::class, $import->getImportedFile());
        self::assertSame('node_modules/bar/foo/hom.js', $import->getImportedFile()->path);
        self::assertSame('bar/foo/hom', $import->getImportedFile()->getName());
    }

    public function testAsRequireAbsoluteFile()
    {
        $parent = new File('node_modules/bar/baz.js');
        $path   = File::clean(__DIR__ . '/../../fixtures/node_modules/bar/foo/hom');

        $import = $this->file_resolver->asRequire($path, $parent);

        self::assertInstanceOf(File::class, $import->getImportedFile());
        self::assertSame($path . '.js', $import->getImportedFile()->path);
        self::assertSame($path . '.js', $import->getImportedFile()->getName());
    }

    public function testAsRequireAbsoluteFileFromModule()
    {
        $parent = new Module('bar/baz', 'node_modules/bar/baz.js');
        $path   = File::clean(__DIR__ . '/../../fixtures/node_modules/bar/foo/hom');

        $import = $this->file_resolver->asRequire($path, $parent);

        self::assertInstanceOf(File::class, $import->getImportedFile());
        self::assertSame($path . '.js', $import->getImportedFile()->path);
        self::assertSame($path . '.js', $import->getImportedFile()->getName());
    }

    public function testAsRequireAbsoluteDir()
    {
        $parent = new File('node_modules/bar/baz.js');
        $path   = File::clean(__DIR__ . '/../../fixtures/node_modules/bar/foo/bar');

        $import = $this->file_resolver->asRequire($path, $parent);

        self::assertInstanceOf(File::class, $import->getImportedFile());
        self::assertSame($path . '/index.js', $import->getImportedFile()->path);
        self::assertSame($path . '/index.js', $import->getImportedFile()->getName());
    }

    public function testAsRequireAbsoluteDirFromModule()
    {
        $parent = new Module('bar/baz', 'node_modules/bar/baz.js');
        $path   = File::clean(__DIR__ . '/../../fixtures/node_modules/bar/foo/bar');

        $import = $this->file_resolver->asRequire($path, $parent);

        self::assertInstanceOf(File::class, $import->getImportedFile());
        self::assertSame($path . '/index.js', $import->getImportedFile()->path);
        self::assertSame($path . '/index.js', $import->getImportedFile()->getName());
    }

    public function testAsRequireAsDir()
    {
        $parent = new File('node_modules/bar/baz.js');
        $import = $this->file_resolver->asRequire('./foo/bar', $parent);

        self::assertInstanceOf(File::class, $import->getImportedFile());
        self::assertSame('node_modules/bar/foo/bar/index.js', $import->getImportedFile()->path);
        self::assertSame('node_modules/bar/foo/bar/index.js', $import->getImportedFile()->getName());
    }

    public function testAsRequireAsDirFromModule()
    {
        $parent = new Module('bar/baz', 'node_modules/bar/baz.js');
        $import = $this->file_resolver->asRequire('./foo/bar', $parent);

        self::assertInstanceOf(Module::class, $import->getImportedFile());
        self::assertSame('node_modules/bar/foo/bar/index.js', $import->getImportedFile()->path);
        self::assertSame('bar/foo/bar', $import->getImportedFile()->getName());
    }

    public function testAsRequireModule()
    {
        $parent = new File('node_modules/bar/baz.js');
        $import = $this->file_resolver->asRequire('jquery', $parent);

        self::assertInstanceOf(Module::class, $import->getImportedFile());
        self::assertSame('node_modules/jquery/jquery.js', $import->getImportedFile()->path);
        self::assertSame('jquery', $import->getImportedFile()->getName());
    }

    public function testAsRequireModuleWithDir()
    {
        $parent = new File('node_modules/bar/baz.js');
        $import = $this->file_resolver->asRequire('module_package_dir', $parent);

        self::assertInstanceOf(Module::class, $import->getImportedFile());
        self::assertSame('node_modules/module_package_dir/src/index.js', $import->getImportedFile()->path);
        self::assertSame('module_package_dir', $import->getImportedFile()->getName());
    }

    public function testAsRequireModuleFromOtherPath()
    {
        $parent = new File('node_modules/bar/baz.js');
        $import = $this->file_resolver->asRequire('uikit', $parent);

        self::assertInstanceOf(Module::class, $import->getImportedFile());
        self::assertSame('some_other_location/uikit/index.js', $import->getImportedFile()->path);
        self::assertSame('uikit', $import->getImportedFile()->getName());
    }

    /**
     * @expectedException \Hostnet\Component\Resolver\Import\Nodejs\Exception\FileNotFoundException
     */
    public function testAsRequireUnknown()
    {
        $this->file_resolver->asRequire('foobar', new File('node_modules/bar/baz.js'));
    }
}

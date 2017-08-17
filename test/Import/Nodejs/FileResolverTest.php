<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import\Nodejs;

use Hostnet\Component\Resolver\Import\File;
use Hostnet\Component\Resolver\Import\Module;
use PHPUnit\Framework\TestCase;

class FileResolverTest extends TestCase
{
    /**
     * @var FileResolver
     */
    private $file_resolver;

    protected function setUp()
    {
        $this->file_resolver = new FileResolver(__DIR__ . '/../../fixtures', ['.js', '.json', '.node']);
    }

    public function testAsRequireFile()
    {
        $parent = new Module('bar/baz', new File('node_modules/bar/baz.js'));

        $import = $this->file_resolver->asRequire('./foo/hom', $parent);

        self::assertInstanceOf(Module::class, $import->getImportedFile());
        self::assertSame('node_modules/bar/foo/hom.js', $import->getImportedFile()->getPath());
        self::assertSame('bar/foo/hom', $import->getImportedFile()->getName());
    }

    public function testAsRequireAsDir()
    {
        $parent = new Module('bar/baz', new File('node_modules/bar/baz.js'));

        $import = $this->file_resolver->asRequire('./foo/bar', $parent);

        self::assertInstanceOf(Module::class, $import->getImportedFile());
        self::assertSame('node_modules/bar/foo/bar/index.js', $import->getImportedFile()->getPath());
        self::assertSame('bar/foo/bar', $import->getImportedFile()->getName());
    }
}

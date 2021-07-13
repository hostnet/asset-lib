<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import\BuiltIn;

use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\FileResolverInterface;
use Hostnet\Component\Resolver\Import\Import;
use Hostnet\Component\Resolver\Import\ImportCollection;
use Hostnet\Component\Resolver\Import\Nodejs\FileResolver;
use Hostnet\Component\Resolver\Module;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @covers \Hostnet\Component\Resolver\Import\BuiltIn\JsImportCollector
 */
class JsImportCollectorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var JsImportCollector
     */
    private $js_import_collector;

    protected function setUp(): void
    {
        $config = $this->prophesize(ConfigInterface::class);
        $config->getProjectRoot()->willReturn(__DIR__ . '/../../fixtures');
        $config->getIncludePaths()->willReturn([]);

        $this->js_import_collector = new JsImportCollector(
            new FileResolver($config->reveal(), ['.js', '.json', '.node']),
            ['js', 'jsx']
        );
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(bool $expected, File $file): void
    {
        self::assertEquals($expected, $this->js_import_collector->supports($file));
    }

    public function supportsProvider(): array
    {
        return [
            [false, new File('foo')],
            [false, new File('foo.ts')],
            [false, new File('foo.less')],
            [true, new File('foo.jsx')],
            [true, new File('foo.js')],
        ];
    }

    public function testCollect(): void
    {
        $imports = new ImportCollection();
        $file    = new File('resolver/js/require-syntax/main.js');

        $this->js_import_collector->collect(__DIR__ . '/../../fixtures', $file, $imports);

        self::assertEquals([
            new Import('./single_quote', new File('resolver/js/require-syntax/single_quote.js')),
            new Import('./double_quote', new File('resolver/js/require-syntax/double_quote.js')),
            new Import('module_index', new Module('module_index', 'node_modules/module_index/index.js')),
            new Import('module_package', new Module('module_package', 'node_modules/module_package/main.js')),
            new Import(
                'module_package_dir',
                new Module('module_package_dir', 'node_modules/module_package_dir/src/index.js')
            ),
            new Import('./relative', new File('resolver/js/require-syntax/relative.js')),
            new Import('../relative', new File('resolver/js/relative.js')),
        ], $imports->getImports());

        self::assertEquals([], $imports->getResources());
    }

    public function testCollectBadRequires(): void
    {
        $imports = new ImportCollection();
        $file    = new File('resolver/js/require-syntax/red_haring.js');

        $this->js_import_collector->collect(__DIR__ . '/../../fixtures', $file, $imports);

        self::assertEquals([], $imports->getImports());
        self::assertEquals([], $imports->getResources());
    }

    public function testCollectRequireException(): void
    {
        $resolver = $this->prophesize(FileResolverInterface::class);
        $imports  = new ImportCollection();

        $resolver->asRequire(Argument::any(), Argument::any())->willThrow(new \RuntimeException());

        $js_import_collector = new JsImportCollector($resolver->reveal());
        $js_import_collector->collect(
            __DIR__ . '/../../fixtures',
            new File('resolver/js/require-syntax/main.js'),
            $imports
        );

        self::assertEquals([], $imports->getImports());
        self::assertEquals([], $imports->getResources());
    }
}

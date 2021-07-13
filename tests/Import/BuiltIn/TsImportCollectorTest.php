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
 * @covers \Hostnet\Component\Resolver\Import\BuiltIn\TsImportCollector
 */
class TsImportCollectorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var TsImportCollector
     */
    private $ts_import_collector;

    protected function setUp(): void
    {
        $config = $this->prophesize(ConfigInterface::class);
        $config->getProjectRoot()->willReturn(__DIR__ . '/../../fixtures');
        $config->getIncludePaths()->willReturn([]);

        $this->ts_import_collector = new TsImportCollector(
            new JsImportCollector(new FileResolver($config->reveal(), ['.ts', '.js', '.json', '.node'])),
            new FileResolver($config->reveal(), ['.ts', '.js', '.json', '.node']),
            ['js', 'ts']
        );
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(bool $expected, File $file): void
    {
        self::assertEquals($expected, $this->ts_import_collector->supports($file));
    }

    public function supportsProvider(): array
    {
        return [
            [false, new File('foo')],
            [true, new File('foo.js')],
            [false, new File('foo.less')],
            [false, new File('foo.jsx')],
            [true, new File('foo.ts')],
        ];
    }

    public function testCollect(): void
    {
        $imports = new ImportCollection();
        $file    = new File('resolver/ts/import-syntax/main.ts');

        $this->ts_import_collector->collect(__DIR__ . '/../../fixtures', $file, $imports);

        self::assertEquals([
            new Import('./Import', new File('resolver/ts/import-syntax/Import.ts')),
            new Import('./DoubleQuote', new File('resolver/ts/import-syntax/DoubleQuote.ts')),
            new Import('./SingleQuote', new File('resolver/ts/import-syntax/SingleQuote.ts')),
            new Import('./Simple', new File('resolver/ts/import-syntax/Simple.ts')),
            new Import('./Alias', new File('resolver/ts/import-syntax/Alias.ts')),
            new Import('./All', new File('resolver/ts/import-syntax/All.ts')),
            new Import('./Multiple', new File('resolver/ts/import-syntax/Multiple.ts')),
            new Import('./Multiple2', new File('resolver/ts/import-syntax/Multiple2.ts')),
            new Import('./module.js', new File('resolver/ts/import-syntax/module.js')),
            new Import('module_index', new Module('module_index', 'node_modules/module_index/index.js')),
            new Import('module_package', new Module('module_package', 'node_modules/module_package/main.js')),
            new Import(
                'module_package_dir',
                new Module('module_package_dir', 'node_modules/module_package_dir/src/index.js')
            ),
            new Import('jquery', new Module('jquery', 'node_modules/jquery/jquery.js')),
            new Import('./Alias', new File('resolver/ts/import-syntax/Alias.ts')),
            new Import('./DoubleQuote', new File('resolver/ts/import-syntax/DoubleQuote.ts')),
            new Import('./Simple', new File('resolver/ts/import-syntax/Simple.ts')),
        ], $imports->getImports());

        self::assertEquals([], $imports->getResources());
    }

    public function testCollectRequireException(): void
    {
        $resolver = $this->prophesize(FileResolverInterface::class);
        $imports  = new ImportCollection();

        $resolver->asRequire(Argument::any(), Argument::any())->willThrow(new \RuntimeException());

        $ts_import_collector = new TsImportCollector(
            new JsImportCollector($resolver->reveal()),
            $resolver->reveal()
        );
        $ts_import_collector->collect(
            __DIR__ . '/../../fixtures',
            new File('resolver/ts/import-syntax/main.ts'),
            $imports
        );

        self::assertEquals([], $imports->getImports());
        self::assertEquals([], $imports->getResources());
    }
}

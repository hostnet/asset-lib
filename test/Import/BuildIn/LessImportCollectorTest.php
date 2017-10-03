<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Import\BuildIn;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\Import;
use Hostnet\Component\Resolver\Import\ImportCollection;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Import\BuildIn\LessImportCollector
 */
class LessImportCollectorTest extends TestCase
{
    /**
     * @var LessImportCollector
     */
    private $less_import_collector;

    protected function setUp()
    {
        $this->less_import_collector = new LessImportCollector();
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports($expected, File $file)
    {
        self::assertEquals($expected, $this->less_import_collector->supports($file));
    }

    public function supportsProvider()
    {
        return [
            [false, new File('foo')],
            [false, new File('foo.ts')],
            [false, new File('foo.jsx')],
            [false, new File('foo.js')],
            [true, new File('foo.less')],
        ];
    }

    public function testCollect()
    {
        $imports = new ImportCollection();
        $file = new File('resolver/less/import-syntax/main.less');

        $this->less_import_collector->collect(__DIR__ . '/../../fixtures', $file, $imports);

        self::assertEquals([
            new Import('double_quote', new File('resolver/less/import-syntax/double_quote.less'), true),
            new Import('single_quote', new File('resolver/less/import-syntax/single_quote.less'), true),
            new Import('./relative', new File('resolver/less/import-syntax/relative.less'), true),
            new Import('../relative', new File('resolver/less/relative.less'), true),
            new Import('options.less', new File('resolver/less/import-syntax/options.less'), true),
            new Import('print.css', new File('resolver/less/import-syntax/print.css'), true),
            new Import('print-tv.css', new File('resolver/less/import-syntax/print-tv.css'), true),
            new Import('base.css', new File('resolver/less/import-syntax/base.css'), true),
            new Import('non-url.css', new File('resolver/less/import-syntax/non-url.css'), true),
            new Import('media-print.css', new File('resolver/less/import-syntax/media-print.css'), true),
            new Import('media-print-extra.css', new File('resolver/less/import-syntax/media-print-extra.css'), true),
        ], $imports->getImports());

        self::assertEquals([], $imports->getResources());
    }
}

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
 * @covers \Hostnet\Component\Resolver\Import\BuildIn\AngularImportCollector
 */
class AngularImportCollectorTest extends TestCase
{
    /**
     * @var AngularImportCollector
     */
    private $angular_import_collector;

    protected function setUp()
    {
        $this->angular_import_collector = new AngularImportCollector();
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports($expected, File $file)
    {
        self::assertEquals($expected, $this->angular_import_collector->supports($file));
    }

    public function supportsProvider()
    {
        return [
            [false, new File('foo')],
            [false, new File('foo.ts')],
            [false, new File('foo.less')],
            [false, new File('foo.jsx')],
            [false, new File('foo.js')],
            [true, new File('app.component.ts')],
        ];
    }

    public function testCollect()
    {
        $imports = new ImportCollection();
        $file = new File('resolver/ts/angular/app.component.ts');

        $this->angular_import_collector->collect(__DIR__ . '/../../fixtures', $file, $imports);

        self::assertEquals([
            new Import(
                'resolver/ts/angular/app.component.html',
                new File('resolver/ts/angular/app.component.html'),
                true
            ),
            new Import(
                'resolver/ts/angular/app.component.less',
                new File('resolver/ts/angular/app.component.less'),
                true
            ),
        ], $imports->getImports());
        self::assertEquals([], $imports->getResources());
    }
}

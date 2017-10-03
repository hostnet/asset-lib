<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Import;

use Hostnet\Component\Resolver\File;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @covers \Hostnet\Component\Resolver\Import\ImportFinder
 */
class ImportFinderTest extends TestCase
{
    /**
     * @var ImportFinder
     */
    private $import_finder;

    protected function setUp()
    {

        $this->import_finder = new ImportFinder(__DIR__);
    }

    public function testAll()
    {
        $file = new File('foo.js');

        $collector = new class implements ImportCollectorInterface {

            public function supports(File $file): bool
            {
                return true;
            }

            public function collect(string $cwd, File $file, ImportCollection $imports)
            {
                $imports->addImport(new Import('bar.js', new File('bar.js')));
                $imports->addResource(new File('asset.js'));
            }
        };

        $this->import_finder->addCollector($collector);

        $root = $this->import_finder->all($file);

        self::assertSame($file, $root->getFile());
        self::assertEquals([
            new Dependency(new File('bar.js')),
            new Dependency(new File('asset.js'), false, true),
        ], $root->getChildren());
    }
}

<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import;

use Hostnet\Component\Resolver\File;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Import\ImportCollection
 */
class ImportCollectionTest extends TestCase
{
    public function testGeneric()
    {
        $collection = new ImportCollection();

        $import1    = new Import('foo', new File('foo.js'));
        $import2    = new Import('baz', new File('baz.js'));
        $resources1 = new File('bar.js');
        $resources2 = new File('fez.js');

        $collection->addImport($import1);
        $collection->addResource($resources1);

        self::assertSame([$import1], $collection->getImports());
        self::assertSame([$resources1], $collection->getResources());

        $other_collection = new ImportCollection();
        $other_collection->addImport($import2);
        $other_collection->addResource($resources2);

        $collection->extends($other_collection);

        self::assertSame([$import1, $import2], $collection->getImports());
        self::assertSame([$resources1, $resources2], $collection->getResources());
    }
}

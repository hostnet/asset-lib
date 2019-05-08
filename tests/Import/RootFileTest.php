<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import;

use Hostnet\Component\Resolver\File;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Import\RootFile
 */
class RootFileTest extends TestCase
{
    public function testGeneric(): void
    {
        $file = new File('foo.js');
        $root = new RootFile($file);
        $dep  = new Dependency(new File('bar.js'));

        $root->addChild($dep);

        self::assertSame($file, $root->getFile());
        self::assertSame([$dep], $root->getChildren());
        self::assertFalse($root->isInlineDependency());
        self::assertFalse($root->isStatic());
    }
}

<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import;

use Hostnet\Component\Resolver\File;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Import\Dependency
 */
class DependencyTest extends TestCase
{
    public function testGeneric(): void
    {
        $file = new File('foo.js');
        $root = new Dependency($file, true, true);
        $dep  = new Dependency(new File('bar.js'));

        $root->addChild($dep);

        self::assertSame($file, $root->getFile());
        self::assertSame([$dep], $root->getChildren());
        self::assertTrue($root->isInlineDependency());
        self::assertTrue($root->isStatic());
    }
}

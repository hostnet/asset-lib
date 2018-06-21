<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\DependencyNodeInterface;
use Hostnet\Component\Resolver\Import\RootFile;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Builder\TreeWalker
 */
class TreeWalkerTest extends TestCase
{
    public function testWalk()
    {
        $seen = [];

        $walker = new TreeWalker(function (DependencyNodeInterface $node) use (&$seen) {
            $seen[] = $node;
        });

        $root        = new RootFile(new File('foo'));
        $child       = new RootFile(new File('foo'));
        $grand_child = new RootFile(new File('foo'));

        $root->addChild($child);
        $child->addChild($grand_child);

        $walker->walk($root);

        self::assertSame([$root, $child, $grand_child], $seen);
    }

    public function testWalkEarlyStop()
    {
        $seen = [];

        $walker = new TreeWalker(function (DependencyNodeInterface $node) use (&$seen) {
            $seen[] = $node;

            return false;
        });

        $root        = new RootFile(new File('foo'));
        $child       = new RootFile(new File('foo'));
        $grand_child = new RootFile(new File('foo'));

        $root->addChild($child);
        $child->addChild($grand_child);

        $walker->walk($root);

        self::assertSame([$root], $seen);
    }

    public function testWalkSkipSubtree()
    {
        $root        = new RootFile(new File('foo'));
        $child       = new RootFile(new File('foo'));
        $grand_child = new RootFile(new File('foo'));

        $root->addChild($child);
        $child->addChild($grand_child);

        $seen = [];

        $walker = new TreeWalker(function (DependencyNodeInterface $node) use (&$seen, $child) {
            $seen[] = $node;

            return $node !== $child;
        });

        $walker->walk($root);

        self::assertSame([$root, $child], $seen);
    }
}

<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Split;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\Dependency;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Split\OneOnOneSplittingStrategy
 */
class OneOnOneSplittingStrategyTest extends TestCase
{
    public function testResolveChunk()
    {
        $one_on_one_splitting_strategy = new OneOnOneSplittingStrategy();

        $dep = new Dependency(new File('/dev/hda1'));
        self::assertEquals('/dev/null', $one_on_one_splitting_strategy->resolveChunk('/dev/null', $dep));
    }
}

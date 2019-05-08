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
    public function testResolveChunk(): void
    {
        $one_on_one_splitting_strategy                = new OneOnOneSplittingStrategy();
        $one_on_one_splitting_strategy_with_exclusion = new OneOnOneSplittingStrategy('', ['/dev/hda1']);
        $one_on_one_splitting_strategy_with_source    = new OneOnOneSplittingStrategy('/dev/', ['hda1']);

        $dep1 = new Dependency(new File('/dev/hda1'));
        $dep2 = new Dependency(new File('/home/user/.bashrc'));
        self::assertEquals('dev/null', $one_on_one_splitting_strategy->resolveChunk('/dev/null', $dep1));
        self::assertEquals('dev/null', $one_on_one_splitting_strategy->resolveChunk('/dev/null', $dep2));
        self::assertEquals(null, $one_on_one_splitting_strategy_with_exclusion->resolveChunk('/dev/null', $dep1));
        self::assertEquals(
            'dev/null',
            $one_on_one_splitting_strategy_with_exclusion->resolveChunk('/dev/null', $dep2)
        );
        self::assertEquals(null, $one_on_one_splitting_strategy_with_source->resolveChunk('/dev/null', $dep1));
        self::assertEquals('null', $one_on_one_splitting_strategy_with_source->resolveChunk('/dev/null', $dep2));
    }
}

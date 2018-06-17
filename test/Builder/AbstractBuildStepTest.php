<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Builder\AbstractBuildStep
 */
class AbstractBuildStepTest extends TestCase
{
    public function testGeneric(): void
    {
        $step = new MockStep();

        self::assertSame(50, $step->buildPriority());
        self::assertSame(serialize([
            \get_class($step),
            $step->acceptedStates(),
            $step->resultingState(),
            $step->acceptedExtension(),
            $step->resultingExtension(),
            $step->getJsModule(),
            $step->buildPriority(),
        ]), $step->getHash());
    }
}

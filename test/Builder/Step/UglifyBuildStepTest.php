<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder\Step;

use Hostnet\Component\Resolver\Builder\AbstractBuildStep;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Builder\Step\UglifyBuildStep
 */
class UglifyBuildStepTest extends TestCase
{
    public function testGeneric(): void
    {
        $step = new UglifyBuildStep();

        self::assertSame([
            AbstractBuildStep::MODULES_COLLECTED,
            AbstractBuildStep::MODULES_READY,
        ], $step->acceptedStates());
        self::assertSame(AbstractBuildStep::MODULES_READY, $step->resultingState());
        self::assertSame('.js', $step->acceptedExtension());
        self::assertSame('.js', $step->resultingExtension());
        self::assertSame(10, $step->buildPriority());
        self::assertContains('js/steps/uglify.js', $step->getJsModule());
    }
}

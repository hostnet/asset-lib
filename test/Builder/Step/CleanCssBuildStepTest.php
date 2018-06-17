<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder\Step;

use Hostnet\Component\Resolver\Builder\AbstractBuildStep;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Builder\Step\CleanCssBuildStep
 */
class CleanCssBuildStepTest extends TestCase
{
    public function testGeneric(): void
    {
        $step = new CleanCssBuildStep();

        self::assertSame([
            AbstractBuildStep::MODULES_COLLECTED,
            AbstractBuildStep::MODULES_READY,
        ], $step->acceptedStates());
        self::assertSame(AbstractBuildStep::MODULES_READY, $step->resultingState());
        self::assertSame('.css', $step->acceptedExtension());
        self::assertSame('.css', $step->resultingExtension());
        self::assertSame(10, $step->buildPriority());
        self::assertContains('js/steps/cleancss.js', $step->getJsModule());
    }
}

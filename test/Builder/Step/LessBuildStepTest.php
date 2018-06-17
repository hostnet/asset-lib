<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder\Step;

use Hostnet\Component\Resolver\Builder\AbstractBuildStep;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Builder\Step\LessBuildStep
 */
class LessBuildStepTest extends TestCase
{
    public function testGeneric(): void
    {
        $step = new LessBuildStep();

        self::assertSame([AbstractBuildStep::FILE_READ], $step->acceptedStates());
        self::assertSame(AbstractBuildStep::FILE_READY, $step->resultingState());
        self::assertSame('.less', $step->acceptedExtension());
        self::assertSame('.css', $step->resultingExtension());
        self::assertContains('js/steps/less.js', $step->getJsModule());
    }
}

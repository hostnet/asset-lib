<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder\Step;

use Hostnet\Component\Resolver\Builder\AbstractBuildStep;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Builder\Step\ModuleBuildStep
 */
class ModuleBuildStepTest extends TestCase
{
    public function testGeneric(): void
    {
        $step = new ModuleBuildStep();

        self::assertSame([AbstractBuildStep::FILE_READY], $step->acceptedStates());
        self::assertSame(AbstractBuildStep::FILE_READY, $step->resultingState());
        self::assertSame('.js', $step->acceptedExtension());
        self::assertSame('.js', $step->resultingExtension());
        self::assertContains('js/steps/module.js', $step->getJsModule());
    }
}

<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder\Step;

use Hostnet\Component\Resolver\Builder\AbstractBuildStep;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Builder\Step\TypescriptBuildStep
 */
class TypescriptBuildStepTest extends TestCase
{
    public function testGeneric(): void
    {
        $step = new TypescriptBuildStep();

        self::assertSame([AbstractBuildStep::FILE_READ], $step->acceptedStates());
        self::assertSame(AbstractBuildStep::FILE_TRANSPILED, $step->resultingState());
        self::assertSame('.ts', $step->acceptedExtension());
        self::assertSame('.js', $step->resultingExtension());
        self::assertContains('js/steps/ts.js', $step->getJsModule());
    }
}

<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder\Step;

use Hostnet\Component\Resolver\Builder\AbstractBuildStep;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Builder\Step\JsBuildStep
 */
class JsBuildStepTest extends TestCase
{
    public function testGeneric(): void
    {
        $step = new JsBuildStep();

        self::assertSame([
            AbstractBuildStep::FILE_READ,
            AbstractBuildStep::FILE_TRANSPILED,
        ], $step->acceptedStates());
        self::assertSame(AbstractBuildStep::FILE_READY, $step->resultingState());
        self::assertSame('.js', $step->acceptedExtension());
        self::assertSame('.js', $step->resultingExtension());
        self::assertContains('js/steps/identity.js', $step->getJsModule());
    }
}

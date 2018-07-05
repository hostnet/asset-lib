<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder\Step;

use Hostnet\Component\Resolver\Builder\AbstractBuildStep;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Builder\Step\CssFontRewriteStep
 */
class CssFontRewriteStepTest extends TestCase
{
    public function testGeneric(): void
    {
        $step = new CssFontRewriteStep();

        self::assertSame([AbstractBuildStep::FILE_READY], $step->acceptedStates());
        self::assertSame(AbstractBuildStep::FILE_READY, $step->resultingState());
        self::assertSame('.css', $step->acceptedExtension());
        self::assertSame('.css', $step->resultingExtension());
        self::assertContains('js/steps/css_rewrite.js', $step->getJsModule());
    }
}

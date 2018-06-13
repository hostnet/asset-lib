<?php

namespace Hostnet\Component\Resolver\Builder\Step;

use Hostnet\Component\Resolver\Builder\AbstractBuildStep;

class CleanCssBuildStep extends AbstractBuildStep
{
    public function acceptedStates(): array
    {
        return [self::MODULES_COLLECTED, self::MODULES_READY];
    }

    public function resultingState(): int
    {
        return self::MODULES_READY;
    }

    public function acceptedExtension(): string
    {
        return '.css';
    }

    public function resultingExtension(): string
    {
        return '.css';
    }

    public function buildPriority(): int
    {
        return 10; // Should be done at the end
    }

    public function getJsModule(): string
    {
        return __DIR__ . '/../js/steps/cleancss.js';
    }
}

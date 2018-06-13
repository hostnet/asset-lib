<?php

namespace Hostnet\Component\Resolver\Builder\Step;

use Hostnet\Component\Resolver\Builder\AbstractBuildStep;

class LessBuildStep extends AbstractBuildStep
{
    public function acceptedStates(): array
    {
        return [self::FILE_READ];
    }

    public function resultingState(): int
    {
        return self::FILE_READY;
    }

    public function acceptedExtension(): string
    {
        return '.less';
    }

    public function resultingExtension(): string
    {
        return '.css';
    }

    public function getJsModule(): string
    {
        return __DIR__ . '/../js/steps/less.js';
    }
}

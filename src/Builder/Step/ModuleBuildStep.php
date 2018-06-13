<?php

namespace Hostnet\Component\Resolver\Builder\Step;

use Hostnet\Component\Resolver\Builder\AbstractBuildStep;

class ModuleBuildStep extends AbstractBuildStep
{
    public function acceptedStates(): array
    {
        return [self::FILE_READ, self::FILE_TRANSPILED];
    }

    public function resultingState(): int
    {
        return self::FILE_READY;
    }

    public function acceptedExtension(): string
    {
        return '.js';
    }

    public function resultingExtension(): string
    {
        return '.js';
    }

    public function getJsModule(): string
    {
        return __DIR__ . '/../js/steps/module.js';
    }
}

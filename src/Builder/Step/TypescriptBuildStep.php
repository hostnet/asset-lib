<?php

namespace Hostnet\Component\Resolver\Builder\Step;

use Hostnet\Component\Resolver\Builder\AbstractBuildStep;

class TypescriptBuildStep extends AbstractBuildStep
{
    public function acceptedStates(): array
    {
        return [self::FILE_READ];
    }

    public function resultingState(): int
    {
        return self::FILE_TRANSPILED;
    }

    public function acceptedExtension(): string
    {
        return '.ts';
    }

    public function resultingExtension(): string
    {
        return '.js';
    }

    public function getJsModule(): string
    {
        return __DIR__ . '/../js/steps/ts.js';
    }
}

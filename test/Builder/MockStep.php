<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

class MockStep extends AbstractBuildStep
{
    public function acceptedStates(): array
    {
        return [AbstractBuildStep::FILE_READ];
    }

    public function resultingState(): int
    {
        return AbstractBuildStep::FILE_READY;
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
        return 'phpunit';
    }
}

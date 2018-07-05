<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder\Step;

use Hostnet\Component\Resolver\Builder\AbstractBuildStep;

/**
 * Most generic build step which just puts the given extension to a ready state. This is useful if you are only
 * interested in supporting an extension without actually doing anything to it. For instance, fonts or images.
 */
class IdentityBuildStep extends AbstractBuildStep
{
    private $extension;

    public function __construct(string $extension)
    {
        $this->extension = $extension;
    }

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
        return $this->extension;
    }

    public function resultingExtension(): string
    {
        return $this->extension;
    }

    public function getJsModule(): string
    {
        return __DIR__ . '/../js/steps/identity.js';
    }
}

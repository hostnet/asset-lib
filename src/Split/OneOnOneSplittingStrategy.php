<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Split;

use Hostnet\Component\Resolver\Import\DependencyNodeInterface;

/**
 * Resolves chunks to always be added.
 */
class OneOnOneSplittingStrategy implements EntryPointSplittingStrategyInterface
{
    public function resolveChunk(string $entry_point, DependencyNodeInterface $dependency): ?string
    {
        return $entry_point;
    }
}

<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Split;

use Hostnet\Component\Resolver\Import\DependencyNodeInterface;

/**
 * In more complex Javascript projects a simple bundler is not enough as often you want to handle
 * cases where you want to split specific chunks in specific output files. For example for big projects
 * it is very common to have one bundled javascript file with common shared code and not include
 * all chunks in one bundled file.
 * If you want to modify the bundling behaviour, then you have to provide a class instance implementing
 * this interface.
 */
interface EntryPointSplittingStrategyInterface
{
    /**
     * Should return a file name where this chunk should be outputted.
     *
     * @param string $entry_point
     * @param DependencyNodeInterface $dependency
     */
    public function resolveChunk(string $entry_point, DependencyNodeInterface $dependency): ?string;
}

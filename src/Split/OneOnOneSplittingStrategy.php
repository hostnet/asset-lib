<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Split;

use Hostnet\Component\Resolver\Import\DependencyNodeInterface;

/**
 * Resolves chunks to always be added, unless on the exclusion list.
 */
final class OneOnOneSplittingStrategy implements EntryPointSplittingStrategyInterface
{
    private $exclude_list;

    public function __construct(array $exclude_list = [])
    {
        $this->exclude_list = array_combine($exclude_list, $exclude_list);
    }

    public function resolveChunk(string $entry_point, DependencyNodeInterface $dependency): ?string
    {
        $dep = $dependency->getFile()->path;
        return isset($this->exclude_list[$dep]) ? null : $entry_point;
    }
}

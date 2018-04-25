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
    private $source_root;

    private $exclude_list;

    public function __construct(string $source_root = '', array $exclude_list = [])
    {
        $this->source_root  = '#^' . preg_quote($source_root, '#') . '#';
        $this->exclude_list = array_combine($exclude_list, $exclude_list);
    }

    public function resolveChunk(string $entry_point, DependencyNodeInterface $dependency): ?string
    {
        $dep = preg_replace($this->source_root, '', $dependency->getFile()->path);
        return isset($this->exclude_list[$dep])
            ? null
            : ltrim(preg_replace($this->source_root, '', $entry_point), '/\\\\');
    }
}

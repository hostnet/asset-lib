<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Cache;

use Psr\SimpleCache\CacheInterface;

/**
 * Implementations of this interface allows for loading and saving.
 */
interface FileCacheInterface extends CacheInterface
{
    /**
     * Save the cache to disk.
     */
    public function save(): void;

    /**
     * Load the cache from disk.
     */
    public function load(): void;
}

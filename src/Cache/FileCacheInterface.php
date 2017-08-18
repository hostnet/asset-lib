<?php

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
    public function save();

    /**
     * Load the cache from disk.
     */
    public function load();
}

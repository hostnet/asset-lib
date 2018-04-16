<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Cache;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\ImportCollection;
use Hostnet\Component\Resolver\Import\ImportCollectorInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Decorator for a ImportCollectorInterface which can cache the result.
 */
final class CachedImportCollector implements ImportCollectorInterface
{
    private $inner;
    private $cache;
    private $file_cache;

    public function __construct(ImportCollectorInterface $inner, CacheInterface $cache)
    {
        $this->inner      = $inner;
        $this->cache      = $cache;
        $this->file_cache = new \SplObjectStorage();
    }

    /**
     * {@inheritdoc}
     */
    public function supports(File $file): bool
    {
        return $this->inner->supports($file);
    }

    /**
     * {@inheritdoc}
     */
    public function collect(string $cwd, File $file, ImportCollection $imports): void
    {
        if (isset($this->file_cache[$file])) {
            $imports->extends($this->file_cache[$file]);
            return;
        }

        $key    = $file->path . get_class($this->inner);
        $path   = File::makeAbsolutePath($file->path, $cwd);
        $fmtime = filemtime($path);

        if ($this->cache->has($key)) {
            $item = $this->cache->get($key);

            // Did the file change? If so, do not use the cached item...
            if (isset($item['deps']) && $item['info'] === $fmtime) {
                $imports->extends($item['deps']);

                $this->file_cache[$file] = $item['deps'];

                return;
            }
        }
        $inner_imports = new ImportCollection();
        $this->inner->collect($cwd, $file, $inner_imports);

        $this->cache->set($key, ['info' => $fmtime, 'deps' => $inner_imports]);

        $this->file_cache[$file] = $inner_imports;

        $imports->extends($inner_imports);
    }
}

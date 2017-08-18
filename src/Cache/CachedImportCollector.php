<?php
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

    public function __construct(ImportCollectorInterface $inner, CacheInterface $cache)
    {
        $this->inner = $inner;
        $this->cache = $cache;
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
    public function collect(string $cwd, File $file, ImportCollection $imports)
    {
        if ($this->cache->has($file->getPath())) {
            $item = $this->cache->get($file->getPath());

            // Did the file change? If so, do not use the cached item...
            if (isset($item['deps']) && $item['info'] === filemtime($cwd . '/' . $file->getPath())) {
                $imports->extends($item['deps']);

                return;
            }
        }
        $inner_imports = new ImportCollection();
        $this->inner->collect($cwd, $file, $inner_imports);

        $this->cache->set($file->getPath(), [
            'info' => filemtime($cwd . '/' . $file->getPath()),
            'deps' => $inner_imports
        ]);

        $imports->extends($inner_imports);
    }
}

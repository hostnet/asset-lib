<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Cache;

use Hostnet\Component\Resolver\Import\ImportCollection;
use Hostnet\Component\Resolver\Import\ImportCollectorInterface;
use Hostnet\Component\Resolver\Import\ImportInterface;
use Psr\SimpleCache\CacheInterface;

class CachedImportCollector implements ImportCollectorInterface
{
    private $inner;
    private $cache;

    public function __construct(ImportCollectorInterface $inner, CacheInterface $cache)
    {
        $this->inner = $inner;
        $this->cache = $cache;
    }

    public function supports(ImportInterface $file): bool
    {
        return $this->inner->supports($file);
    }

    public function collect(string $cwd, ImportInterface $file, ImportCollection $imports)
    {
        if ($this->cache->has($file->getPath())) {
            $item = $this->cache->get($file->getPath());

            // Do we have dependencies set?
            if (isset($item['deps'])) {
                $imports->extends($item['deps']);
            }
        }
        $inner_imports = new ImportCollection();
        $this->inner->collect($cwd, $file, $inner_imports);

        $this->cache->set($file->getPath(), ['info' => $file, 'deps' => $inner_imports]);

        $imports->extends($inner_imports);
    }
}

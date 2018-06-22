<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Builder\AbstractBuildStep;
use Hostnet\Component\Resolver\Builder\AbstractWriter;
use Hostnet\Component\Resolver\Builder\BuildConfig;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\Import\ImportCollectorInterface;
use Hostnet\Component\Resolver\Import\MutableImportFinderInterface;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Psr\SimpleCache\CacheInterface;

class PluginApi
{
    private $finder;
    private $config;
    private $cache;
    private $build_config;

    public function __construct(
        MutableImportFinderInterface $finder,
        ConfigInterface $config,
        CacheInterface $cache,
        BuildConfig $build_config
    ) {
        $this->finder       = $finder;
        $this->config       = $config;
        $this->cache        = $cache;
        $this->build_config = $build_config;
    }

    public function getNodeJsExecutable(): Executable
    {
        return $this->config->getNodeJsExecutable();
    }

    public function addBuildStep(AbstractBuildStep $build_step): void
    {
        $this->build_config->registerStep($build_step);
    }

    public function addWriter(AbstractWriter $writer): void
    {
        $this->build_config->registerWriter($writer);
    }

    public function addCollector(ImportCollectorInterface $import_collector): void
    {
        $this->finder->addCollector($import_collector);
    }

    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }
}

<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Bundler\Pipeline\ContentPipelineInterface;
use Hostnet\Component\Resolver\Bundler\Pipeline\MutableContentPipelineInterface;
use Hostnet\Component\Resolver\Bundler\Processor\ContentProcessorInterface;
use Hostnet\Component\Resolver\Bundler\Runner\RunnerInterface;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\Import\ImportCollectorInterface;
use Hostnet\Component\Resolver\Import\ImportFinderInterface;
use Hostnet\Component\Resolver\Import\MutableImportFinderInterface;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Psr\SimpleCache\CacheInterface;

class PluginApi
{
    private $pipeline;
    private $finder;
    private $config;
    private $cache;

    public function __construct(
        MutableContentPipelineInterface $pipeline,
        MutableImportFinderInterface $finder,
        ConfigInterface $config,
        CacheInterface $cache
    ) {
        $this->pipeline = $pipeline;
        $this->finder   = $finder;
        $this->config   = $config;
        $this->cache    = $cache;
    }

    public function getNodeJsExecutable(): Executable
    {
        return $this->config->getNodeJsExecutable();
    }

    public function addProcessor(ContentProcessorInterface $processor): void
    {
        $this->pipeline->addProcessor($processor);
    }

    public function addCollector(ImportCollectorInterface $import_collector): void
    {
        $this->finder->addCollector($import_collector);
    }

    public function getPipeline(): ContentPipelineInterface
    {
        return $this->pipeline;
    }

    public function getFinder(): ImportFinderInterface
    {
        return $this->finder;
    }

    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function getRunner(): RunnerInterface
    {
        return $this->config->getRunner();
    }
}

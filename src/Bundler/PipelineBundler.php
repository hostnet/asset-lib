<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler;

use Hostnet\Component\Resolver\Bundler\Pipeline\ContentPipeline;
use Hostnet\Component\Resolver\Bundler\Pipeline\FileReader;
use Hostnet\Component\Resolver\ConfigInterface;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\ImportFinderInterface;
use Psr\Log\LoggerInterface;

class PipelineBundler
{
    private $finder;
    private $pipeline;
    private $logger;
    private $config;

    public function __construct(
        ImportFinderInterface $finder,
        ContentPipeline $pipeline,
        LoggerInterface $logger,
        ConfigInterface $config
    ) {
        $this->finder   = $finder;
        $this->pipeline = $pipeline;
        $this->logger   = $logger;
        $this->config   = $config;
    }

    /**
     * Execute the bundler. This will compile all the entry points and assets
     * defined in the config.
     */
    public function execute()
    {
        $output_folder = $this->config->getWebRoot() . '/' . $this->config->getOutputFolder();
        $source_dir = (!empty($this->config->getSourceRoot()) ? $this->config->getSourceRoot() . '/' : '');

        $file_reader = new FileReader($this->config->cwd());

        // Entry points
        foreach ($this->config->getEntryPoints() as $file_name) {
            $file        = new File($source_dir . $file_name);
            $entry_point = new EntryPoint($file, $this->finder->all($file));

            $this->logger->debug('Checking entry-point bundle file {name}', ['name' => $entry_point->getFile()->path]);

            // bundle
            $this->pipeline->push($entry_point->getBundleFiles(), $entry_point->getBundleFile($output_folder), $file_reader);

            $this->logger->debug('Checking entry-point vendor file {name}', ['name' => $entry_point->getFile()->path]);

            // vendor
            $this->pipeline->push($entry_point->getVendorFiles(), $entry_point->getVendorFile($output_folder), $file_reader);

            // assets
            foreach ($entry_point->getAssetFiles() as $file) {
                // peek for the extension... since we do not know it.
                $asset = new Asset($file, $this->finder->all($file), $this->pipeline->peek($file));

                $this->logger->debug('Checking asset {name}', ['name' => $asset->getFile()->path]);

                $this->pipeline->push($asset->getFiles(), $asset->getAssetFile($output_folder), $file_reader);
            }
        }

        // Assets
        foreach ($this->config->getAssetFiles() as $file_name) {
            $file  = new File($source_dir . $file_name);
            $asset = new Asset($file, $this->finder->all($file), $this->pipeline->peek($file));

            $this->logger->debug('Checking asset {name}', ['name' => $asset->getFile()->path]);

            $this->pipeline->push($asset->getFiles(), $asset->getAssetFile($output_folder), $file_reader);
        }
    }
}

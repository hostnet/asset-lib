<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler;

use Hostnet\Component\Resolver\Bundler\Pipeline\ContentPipelineInterface;
use Hostnet\Component\Resolver\Bundler\Runner\UglifyJsRunner;
use Hostnet\Component\Resolver\Cache\Cache;
use Hostnet\Component\Resolver\ConfigInterface;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\ReaderInterface;
use Hostnet\Component\Resolver\FileSystem\StringReader;
use Hostnet\Component\Resolver\FileSystem\WriterInterface;
use Hostnet\Component\Resolver\Import\Dependency;
use Hostnet\Component\Resolver\Import\DependencyNodeInterface;
use Hostnet\Component\Resolver\Import\ImportFinderInterface;
use Psr\Log\LoggerInterface;

class PipelineBundler
{
    private $finder;
    private $pipeline;
    private $logger;
    private $config;
    private $uglify_js_runner;

    public function __construct(
        ImportFinderInterface $finder,
        ContentPipelineInterface $pipeline,
        LoggerInterface $logger,
        ConfigInterface $config,
        UglifyJsRunner $uglify_js_runner
    ) {
        $this->finder           = $finder;
        $this->pipeline         = $pipeline;
        $this->logger           = $logger;
        $this->config           = $config;
        $this->uglify_js_runner = $uglify_js_runner;
    }

    /**
     * Execute the bundler. This will compile all the entry points and assets
     * defined in the config.
     *
     * @param ReaderInterface $reader
     * @param WriterInterface $writer
     */
    public function execute(ReaderInterface $reader, WriterInterface $writer)
    {
        $output_folder  = $this->config->getWebRoot();
        $output_folder .= (!empty($output_folder) ? '/' : '') . $this->config->getOutputFolder();
        $source_dir     = (!empty($this->config->getSourceRoot()) ? $this->config->getSourceRoot() . '/' : '');

        // put the require.js in the web folder
        $require_file        = new File(File::clean(__DIR__ . '/../Resources/require.js'));
        $output_require_file = new File($output_folder . '/require.js');

        if ($this->checkIfAnyChanged($output_require_file, [new Dependency($require_file)])) {
            $this->logger->debug('Writing require.js file to {name}', ['name' => $output_require_file->path]);

            // Create an item for the file to write to disk.
            $item = new ContentItem(
                $require_file,
                $output_require_file->getName(),
                new StringReader($reader->read($require_file))
            );

            $writer->write($output_require_file, $this->uglify_js_runner->execute($item));
        }

        // Entry points
        foreach ($this->config->getEntryPoints() as $file_name) {
            $file        = new File($source_dir . $file_name);
            $entry_point = new EntryPoint($this->finder->all($file));

            $this->logger->debug('Checking entry-point bundle file {name}', ['name' => $entry_point->getFile()->path]);

            // bundle
            $this->write(
                $entry_point->getBundleFiles(),
                $entry_point->getBundleFile($output_folder),
                $reader,
                $writer
            );

            $this->logger->debug('Checking entry-point vendor file {name}', ['name' => $entry_point->getFile()->path]);

            // vendor
            $this->write(
                $entry_point->getVendorFiles(),
                $entry_point->getVendorFile($output_folder),
                $reader,
                $writer
            );

            // assets
            foreach ($entry_point->getAssetFiles() as $file) {
                // peek for the extension... since we do not know it.
                $asset = new Asset($this->finder->all($file), $this->pipeline->peek($file));

                $this->logger->debug('Checking asset {name}', ['name' => $asset->getFile()->path]);

                $this->write(
                    $asset->getFiles(),
                    $asset->getAssetFile($output_folder, $this->config->getSourceRoot()),
                    $reader,
                    $writer
                );
            }
        }

        // Assets
        foreach ($this->config->getAssetFiles() as $file_name) {
            $file  = new File($source_dir . $file_name);
            $asset = new Asset($this->finder->all($file), $this->pipeline->peek($file));

            $this->logger->debug('Checking asset {name}', ['name' => $asset->getFile()->path]);

            $this->write(
                $asset->getFiles(),
                $asset->getAssetFile($output_folder, $this->config->getSourceRoot()),
                $reader,
                $writer
            );
        }
    }

    /**
     * @param DependencyNodeInterface[] $dependencies
     * @param File                      $target
     * @param ReaderInterface           $reader
     * @param WriterInterface           $writer
     */
    private function write(array $dependencies, File $target, ReaderInterface $reader, WriterInterface $writer): void
    {
        if ($this->config->isDev() && !$this->checkIfAnyChanged($target, $dependencies)) {
            $this->logger->debug(' * Target already up to date');
            return;
        }

        $writer->write($target, $this->pipeline->push($dependencies, $reader, $target));
    }

    /**
     * Check if the output file is newer than the input files.
     *
     * @param File                      $output_file
     * @param DependencyNodeInterface[] $input_files
     * @return bool
     */
    private function checkIfAnyChanged(File $output_file, array $input_files): bool
    {
        // did the sources change?
        $sources_file  = $this->config->getCacheDir() . '/' . Cache::createFileCacheKey($output_file) . '.sources';
        $input_sources = array_map(function (DependencyNodeInterface $d) {
            return $d->getFile()->path;
        }, $input_files);

        sort($input_sources);

        if (!file_exists($sources_file)) {
            // make sure the cache dir exists
            if (!is_dir(dirname($sources_file))) {
                mkdir(dirname($sources_file), 0777, true);
            }
            file_put_contents($sources_file, serialize($input_sources));

            return true;
        }

        $sources = unserialize(file_get_contents($sources_file), []);

        if (count(array_diff($sources, $input_sources)) > 0 || count(array_diff($input_sources, $sources)) > 0) {
            file_put_contents($sources_file, serialize($input_sources));

            return true;
        }

        // Did the files change?
        $file_path = File::makeAbsolutePath($output_file->path, $this->config->cwd());
        $mtime     = file_exists($file_path) ? filemtime($file_path) : -1;

        if ($mtime === -1) {
            return true;
        }

        foreach ($input_files as $input_file) {
            $path = File::makeAbsolutePath($input_file->getFile()->path, $this->config->cwd());

            if ($mtime < filemtime($path)) {
                return true;
            }
        }

        return false;
    }
}

<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler;

use Hostnet\Component\Resolver\Bundler\Pipeline\ContentPipelineInterface;
use Hostnet\Component\Resolver\Bundler\Runner\RunnerInterface;
use Hostnet\Component\Resolver\Bundler\Runner\RunnerType;
use Hostnet\Component\Resolver\Cache\Cache;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\Event\BundleEvent;
use Hostnet\Component\Resolver\Event\BundleEvents;
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
    private $runner;

    public function __construct(
        ImportFinderInterface $finder,
        ContentPipelineInterface $pipeline,
        LoggerInterface $logger,
        ConfigInterface $config,
        RunnerInterface $runner
    ) {
        $this->finder   = $finder;
        $this->pipeline = $pipeline;
        $this->logger   = $logger;
        $this->config   = $config;
        $this->runner   = $runner;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(ReaderInterface $reader, WriterInterface $writer): void
    {
        $this->config->getEventDispatcher()->dispatch(BundleEvents::PRE_BUNDLE, new BundleEvent());

        try {
            $output_folder = $this->config->getOutputFolder();
            $source_dir    = (!empty($this->config->getSourceRoot()) ? $this->config->getSourceRoot() . '/' : '');

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

                $writer->write($output_require_file, $this->runner->execute(RunnerType::UGLIFY, $item));
            }

            $excludes = $this->getAllExcludedFiles($this->config->getExcludeImports());

            // Entry points
            foreach ($this->config->getEntryPoints() as $file_name) {
                $file        = new File($source_dir . $file_name);
                $entry_point = new EntryPoint($this->finder->all($file));

                $this->logger->debug(
                    'Checking entry-point bundle file {name}',
                    ['name' => $entry_point->getFile()->path]
                );

                // bundle
                $this->write(
                    array_filter(
                        $entry_point->getBundleFiles(),
                        function (DependencyNodeInterface $node) use ($excludes) {
                            return !in_array($node->getFile()->path, $excludes);
                        }
                    ),
                    $entry_point->getBundleFile($output_folder),
                    $reader,
                    $writer
                );

                $this->logger->debug(
                    'Checking entry-point vendor file {name}',
                    ['name' => $entry_point->getFile()->path]
                );

                // vendor
                $this->write(
                    array_filter(
                        $entry_point->getVendorFiles(),
                        function (DependencyNodeInterface $node) use ($excludes) {
                            return !in_array($node->getFile()->path, $excludes);
                        }
                    ),
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
                        array_filter($asset->getFiles(), function (DependencyNodeInterface $node) use ($excludes) {
                            return !in_array($node->getFile()->path, $excludes);
                        }),
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
        } finally {
            $this->config->getEventDispatcher()->dispatch(BundleEvents::POST_BUNDLE, new BundleEvent());
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
        $file_path = File::makeAbsolutePath($output_file->path, $this->config->getProjectRoot());
        $mtime     = file_exists($file_path) ? filemtime($file_path) : -1;

        if ($mtime === -1) {
            return true;
        }

        foreach ($input_files as $input_file) {
            $path = File::makeAbsolutePath($input_file->getFile()->path, $this->config->getProjectRoot());

            if ($mtime < filemtime($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build a full dependency tree and flatten it for all excludes, this will
     * exclude all underlying files too.
     *
     * @param string[] $files
     * @return string[]
     */
    private function getAllExcludedFiles(array $files): array
    {
        if (empty($files)) {
            return [];
        }

        // Build the dependency tree, this way we can exclude everything depended on it.
        return array_map(function (DependencyNodeInterface $node) {
            return $node->getFile()->path;
        }, array_merge(...array_map(function (string $file) {
            $root = $this->finder->all(new File($file));
            $all  = [$root];

            (new TreeWalker(function (DependencyNodeInterface $dependency) use (&$all) {
                $all[] = $dependency;
            }))->walk($root);

            return $all;
        }, $files)));
    }
}

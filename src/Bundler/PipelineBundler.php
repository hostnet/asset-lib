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
use Hostnet\Component\Resolver\Report\ReporterInterface;

class PipelineBundler
{
    private $finder;
    private $pipeline;
    private $config;
    private $runner;

    public function __construct(
        ImportFinderInterface $finder,
        ContentPipelineInterface $pipeline,
        ConfigInterface $config,
        RunnerInterface $runner
    ) {
        $this->finder   = $finder;
        $this->pipeline = $pipeline;
        $this->config   = $config;
        $this->runner   = $runner;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(ReaderInterface $reader, WriterInterface $writer): void
    {
        $reporter = $this->config->getReporter();

        $this->config->getEventDispatcher()->dispatch(BundleEvents::PRE_BUNDLE, new BundleEvent());

        try {
            $output_folder = $this->config->getOutputFolder();
            $source_dir    = (!empty($this->config->getSourceRoot()) ? $this->config->getSourceRoot() . '/' : '');

            // put the require.js in the web folder
            $require_file        = new File(File::clean(__DIR__ . '/../Resources/require.js'));
            $output_require_file = new File($output_folder . '/require.js');

            if ($this->checkIfAnyChanged($output_require_file, [new Dependency($require_file)])) {
                // Create an item for the file to write to disk.
                $item = new ContentItem(
                    $require_file,
                    $output_require_file->getName(),
                    new StringReader($reader->read($require_file))
                );

                $content = $this->runner->execute(RunnerType::UGLIFY, $item);

                $reporter->reportFileState($output_require_file, ReporterInterface::STATE_BUILT);
                $reporter->reportFileContent($output_require_file, $content);

                $writer->write($output_require_file, $content);

                $reporter->reportOutputFile($output_require_file);
            } else {
                $reporter->reportFileState($output_require_file, ReporterInterface::STATE_UP_TO_DATE);
                $reporter->reportOutputFile($output_require_file);
            }

            // Entry points
            foreach ($this->config->getEntryPoints() as $file_name) {
                $file           = new File($source_dir . $file_name);
                $entry_point    = new EntryPoint($this->finder->all($file), $this->config->getSplitStrategy());
                $files_to_build = $entry_point->getFilesToBuild($output_folder);
                if (empty($files_to_build)) {
                    throw new \RuntimeException(
                        sprintf('%s did not resolve in any output file', $file_name)
                    );
                }

                foreach ($files_to_build as $input => $dependencies) {
                    // bundle
                    $this->write(
                        $dependencies,
                        new File($input),
                        $reader,
                        $writer
                    );
                }
            }

            // Assets
            foreach ($this->config->getAssetFiles() as $file_name) {
                $file  = new File($source_dir . $file_name);
                $asset = new Asset($this->finder->all($file), $this->pipeline->peek($file));

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
        $reporter = $this->config->getReporter();

        $reporter->reportFileDependencies($target, $dependencies);
        if ($this->config->isDev() && !$this->checkIfAnyChanged($target, $dependencies)) {
            $reporter->reportFileState($target, ReporterInterface::STATE_UP_TO_DATE);
            $reporter->reportOutputFile($target);
            return;
        }

        $content = $this->pipeline->push($dependencies, $reader, $target);

        $reporter->reportFileState($target, ReporterInterface::STATE_BUILT);
        $reporter->reportFileContent($target, $content);

        $writer->write($target, $content);

        $reporter->reportOutputFile($target);
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
}

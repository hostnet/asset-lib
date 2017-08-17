<?php
namespace Hostnet\Component\Resolver\Bundler;

use Hostnet\Component\Resolver\Import\Dependency;
use Hostnet\Component\Resolver\Import\EntryPoint;
use Hostnet\Component\Resolver\Import\File;
use Hostnet\Component\Resolver\Import\ImportInterface;
use Hostnet\Component\Resolver\Transform\Transformer;
use Hostnet\Component\Resolver\Transform\TransformerInterface;
use Hostnet\Component\Resolver\Transpile\JsModuleWrapperInterface;
use Hostnet\Component\Resolver\Transpile\TranspileException;
use Hostnet\Component\Resolver\Transpile\TranspileResult;
use Hostnet\Component\Resolver\Transpile\TranspilerInterface;
use Psr\Log\LoggerInterface;

/**
 * Bundler for entry points. This can transpile resources and writes them to files.
 */
class Bundler
{
    private $cwd;
    private $transpiler;
    private $transformer;
    private $module_wrapper;
    private $logger;
    private $web_root;
    private $output_dir;
    private $cache_dir;
    private $use_cacheing;

    public function __construct(
        string $cwd,
        TranspilerInterface $transpiler,
        TransformerInterface $transformer,
        JsModuleWrapperInterface $module_wrapper,
        LoggerInterface $logger,
        string $web_root,
        string $output_dir,
        string $cache_dir,
        bool $use_cacheing = false
    ) {
        $this->cwd = $cwd;
        $this->transpiler = $transpiler;
        $this->transformer = $transformer;
        $this->module_wrapper = $module_wrapper;
        $this->logger = $logger;
        $this->web_root = $web_root;
        $this->output_dir = $output_dir;
        $this->cache_dir = $cache_dir;
        $this->use_cacheing = $use_cacheing;
    }

    /**
     * Bundle a list of entry points, each entry point will be written to their
     * own file.
     *
     * @param EntryPoint[] $entry_points
     */
    public function bundle(array $entry_points)
    {
        $output_folder = $this->web_root . '/' . $this->output_dir;

        foreach ($entry_points as $entry_point) {
            $this->logger->debug('Checking entry-point {name}', ['name' => $entry_point->getFile()->getPath()]);

            [$bundle_changed, $vendor_changed] = $this->needsRecompile($entry_point);

            if ($bundle_changed) {
                $this->logger->debug(' * Compiling bundle for {name}', ['name' => $entry_point->getFile()->getPath()]);

                $this->compileFile($entry_point->getBundleFile($output_folder), $entry_point->getBundleFiles());
            } else {
                $this->logger->debug(' * Nothing to do for bundle');
            }

            if ($vendor_changed) {
                $this->logger->debug(' * Compiling vendors for {name}', ['name' => $entry_point->getFile()->getPath()]);

                $this->compileFile($entry_point->getVendorFile($output_folder), $entry_point->getVendorFiles());
            } else {
                $this->logger->debug(' * Nothing to do for vendor');
            }

            $this->compileAsset($entry_point->getAssetFiles());
        }
    }

    /**
     * Bundle a list of assets, each asset will be written to their own file.
     *
     * @param EntryPoint[] $entry_points
     */
    public function compile(array $entry_points)
    {
        foreach ($entry_points as $entry_point) {
            $this->logger->debug('Checking asset {name}', ['name' => $entry_point->getFile()->getPath()]);

            $this->compileAsset(array_map(function (Dependency $d) {
                return $d->getImport();
            }, array_filter($entry_point->getBundleFiles(), function (Dependency $d) {
                return !$d->isVirtual();
            })));
        }
    }

    /**
     * @param ImportInterface[] $asset_files
     */
    private function compileAsset(array $asset_files)
    {
        $output_folder = $this->web_root . '/' . $this->output_dir;

        foreach ($asset_files as $asset_file) {
            if (false !== ($i = strpos($asset_file->getDirectory(), '/'))) {
                $base_dir = substr($asset_file->getDirectory(), $i);
            } else {
                $base_dir = '';
            }

            $output_file = new File(
                $output_folder . $base_dir . '/' . $asset_file->getBaseName() . '.' . $this->transpiler->getExtensionFor($asset_file)
            );

            if (!file_exists($this->cwd . '/' . $output_file->getDirectory())) {
                mkdir($this->cwd . '/' . $output_file->getDirectory(), 0777, true);
            }

            if ($this->checkIfChangedForAll($output_file, [new Dependency($asset_file)])) {
                // Transpile
                $result = $this->getCompiledContentForCached($asset_file);

                // Transform PRE_WRITE
                $content = $this->transformer->onPreWrite($output_file, $result->getContent(), $this->output_dir);

                file_put_contents($this->cwd . '/' . $output_file->getPath(), $content);
            }
        }
    }

    /**
     * Compile a file.
     *
     * @param File         $output_file
     * @param Dependency[] $dependencies
     */
    private function compileFile(File $output_file, array $dependencies)
    {
        // make sure folder exists
        if (!file_exists($this->cwd . '/' . $output_file->getDirectory())) {
            mkdir($this->cwd . '/' . $output_file->getDirectory(), 0777, true);
        }

        $output_content = '';

        try {
            foreach ($dependencies as $dependency) {
                if ($dependency->isVirtual()) {
                    continue;
                }

                $file = $dependency->getImport();

                $result = $this->getCompiledContentForCached($file);

                // Wrap
                $content = $this->module_wrapper->wrapModule(
                    $file->getPath(), // Use the old file, since we need to resolve dependecies
                    $result->getModuleName(),
                    $result->getContent()
                );

                $output_content .= $content;
            }
        } catch (TranspileException $e) {
            $this->logger->error($e->getErrorOutput());

            throw $e;
        }

        // Transform PRE_WRITE
        $output_content = $this->transformer->onPreWrite($output_file, $output_content, $this->output_dir);

        file_put_contents($this->cwd . '/' . $output_file->getPath(), $output_content);
    }

    private function getCompiledContentForCached(ImportInterface $file): TranspileResult
    {
        $new_ext     = $this->transpiler->getExtensionFor($file);
        $output_file = new File($file->getDirectory() . '/' . $file->getBaseName() . '.' . $new_ext);

        if (!$this->use_cacheing) {
            return $this->getCompiledContentFor($file, $output_file);
        }

        $cache_key = substr(md5($output_file->getPath()), 0, 5) . '_' . str_replace('/', '.', $output_file->getPath());

        if ($this->checkIfChanged(
            $this->cache_dir . '/' . $cache_key,
            $this->cwd . '/' . $file->getPath()
        )) {
            $result = $this->getCompiledContentFor($file, $output_file);

            file_put_contents(
                $this->cache_dir . '/' . $cache_key,
                serialize([$result->getModuleName(), $result->getContent()])
            );

            $module_name = $result->getModuleName();
            $content = $result->getContent();
        } else {
            $this->logger->debug('  - Emitting {name} (from cache)', ['name' => $file->getPath()]);

            [$module_name, $content] = unserialize(file_get_contents(
                $this->cache_dir . '/' . $cache_key
            ), []);
        }

        return new TranspileResult($module_name, $content);
    }

    private function getCompiledContentFor(ImportInterface $file, ImportInterface $output_file): TranspileResult
    {
        $this->logger->debug('  - Emitting {name}', ['name' => $file->getPath()]);

        // Transpile
        $result = $this->transpiler->transpile($file);
        $module_name = $result->getModuleName();

        // Transform
        $content = $this->transformer->onPostTranspile(
            $output_file,
            $result->getContent(),
            $this->output_dir
        );

        return new TranspileResult($module_name, $content);
    }

    /**
     * Check if there needs to be a recompile. This check if the bundle and the
     * vendor needs a recompile.
     *
     * NOTE: if there is no vendor file, this should always return false.
     *
     * @param EntryPoint $entry_point
     * @return bool[]
     */
    private function needsRecompile(EntryPoint $entry_point): array
    {
        $output_folder = $this->web_root . '/' . $this->output_dir;

        return [
            $this->checkIfChangedForAll($entry_point->getBundleFile($output_folder), $entry_point->getBundleFiles()),
            $this->checkIfChangedForAll($entry_point->getVendorFile($output_folder), $entry_point->getVendorFiles()),
        ];
    }

    /**
     * @param ImportInterface $output_file
     * @param Dependency[]    $input_files
     * @return bool
     */
    private function checkIfChangedForAll(ImportInterface $output_file, array $input_files): bool
    {
        if ($this->use_cacheing) {
            // did the sources change?
            $sources_file = $this->cache_dir . '/' . substr(md5($output_file->getPath()), 0, 5) . '_' . str_replace(
                    '/',
                    '.',
                    $output_file->getPath()
                ) . '.sources';
            $input_sources = array_map(
                function (Dependency $d) {
                    return $d->getImport()->getPath();
                },
                $input_files
            );

            sort($input_sources);

            if (!file_exists($sources_file)) {
                file_put_contents($sources_file, serialize($input_sources));

                return true;
            }

            $sources = unserialize(file_get_contents($sources_file), []);

            if (count(array_diff($sources, $input_sources)) > 0 || count(array_diff($input_sources, $sources)) > 0) {
                file_put_contents($sources_file, serialize($input_sources));

                return true;
            }
        }

        // Did the files change?
        $file_path = $this->cwd . '/' . $output_file->getPath();
        $mtime = file_exists($file_path) ? filemtime($file_path) : -1;

        if ($mtime === -1) {
            return true;
        }

        foreach ($input_files as $input_file) {
            if ($mtime < filemtime($this->cwd . '/' . $input_file->getImport()->getPath())) {
                return true;
            }
        }

        return false;
    }

    private function checkIfChanged(string $output_file, string $input_file): bool
    {
        $mtime = file_exists($output_file) ? filemtime($output_file) : -1;

        if ($mtime === -1) {
            return true;
        }

        return $mtime < filemtime($input_file);
    }
}

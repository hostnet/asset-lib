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

    public function __construct(
        string $cwd,
        TranspilerInterface $transpiler,
        TransformerInterface $transformer,
        JsModuleWrapperInterface $module_wrapper,
        LoggerInterface $logger,
        string $web_root,
        string $output_dir
    ) {
        $this->cwd = $cwd;
        $this->transpiler = $transpiler;
        $this->transformer = $transformer;
        $this->module_wrapper = $module_wrapper;
        $this->logger = $logger;
        $this->web_root = $web_root;
        $this->output_dir = $output_dir;
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
            }, $entry_point->getBundleFiles()));
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

            if ($this->checkIfChanged($output_file, [new Dependency($asset_file)])) {
                if (!file_exists($this->cwd . '/' . $output_file->getDirectory())) {
                    mkdir($this->cwd . '/' . $output_file->getDirectory(), 0777, true);
                }

                $this->logger->debug(' * Compiling asset {dest}', ['dest' => $output_file->getPath()]);

                // Transpile
                $result = $this->transpiler->transpile($asset_file);
                // Transform
                $content = $this->transformer->transform(
                    $asset_file,
                    $result->getContent(),
                    $this->output_dir
                );

                file_put_contents($this->cwd . '/' . $output_file->getPath(), $content);
            } else {
                $this->logger->debug(' * Nothing to do for asset');
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

        $f = fopen($this->cwd . '/' . $output_file->getPath(), 'wb+');

        try {
            foreach ($dependencies as $dependency) {
                if ($dependency->isVirtual()) {
                    continue;
                }

                $this->logger->debug('  - Emitting {name}', ['name' => $dependency->getImport()->getPath()]);

                // Transpile
                $result = $this->transpiler->transpile($dependency->getImport());
                // Transform
                $content = $this->transformer->transform(
                    $dependency->getImport(),
                    $result->getContent(),
                    $this->output_dir
                );
                // Wrap
                $content = $this->module_wrapper->wrapModule(
                    $dependency->getImport()->getPath(),
                    $result->getModuleName(),
                    $content
                );

                fwrite($f, $content);
            }
        } catch (TranspileException $e) {
            fclose($f);
            unlink($this->cwd . '/' . $output_file->getPath());

            $this->logger->error($e->getTranspilerOutput());

            throw $e;
        }

        fclose($f);
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
            $this->checkIfChanged($entry_point->getBundleFile($output_folder), $entry_point->getBundleFiles()),
            $this->checkIfChanged($entry_point->getVendorFile($output_folder), $entry_point->getVendorFiles()),
        ];
    }

    private function checkIfChanged(ImportInterface $output_file, array $input_files): bool
    {
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
}

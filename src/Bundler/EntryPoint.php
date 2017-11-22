<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\DependencyNodeInterface;

/**
 * Entry points are the starting files for your application. This can be main
 * files, stylesheets or javascript libraries.
 */
final class EntryPoint
{
    private $file;
    private $bundle_files;
    private $vendor_files;
    private $asset_files;

    public function __construct(DependencyNodeInterface $file)
    {
        $this->file = $file->getFile();

        // Split the input files into bundle and vendor files.
        $this->bundle_files = [$file];
        $this->vendor_files = [];
        $this->asset_files  = [];

        $walker = new TreeWalker(function (DependencyNodeInterface $dependency) {
            if ($dependency->isStatic()) {
                $this->asset_files[] = $dependency->getFile();
            } elseif (0 === strpos($dependency->getFile()->path, 'node_modules/')) {
                $this->vendor_files[] = $dependency;
            } else {
                $this->bundle_files[] = $dependency;
            }
        });

        $walker->walk($file);
    }

    /**
     * Return the root file for the entry point.
     *
     * @return File
     */
    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * Return all non-vendor files.
     *
     * @return DependencyNodeInterface[]
     */
    public function getBundleFiles(): array
    {
        return $this->bundle_files;
    }

    /**
     * Return all vendor files.
     *
     * @return DependencyNodeInterface[]
     */
    public function getVendorFiles(): array
    {
        return $this->vendor_files;
    }

    /**
     * Return all assets.
     *
     * @return File[]
     */
    public function getAssetFiles(): array
    {
        return $this->asset_files;
    }

    /**
     * Bundle files are located in the application itself.
     *
     * @param string $output_dir
     * @return File
     */
    public function getBundleFile(string $output_dir): File
    {
        return new File($output_dir . '/' . sprintf(
            '%s.bundle.%s',
            $this->file->getBaseName(),
            'js'
        ));
    }

    /**
     * Vendor files are third party files, usually located in node_modules.
     *
     * @param string $output_dir
     * @return File
     */
    public function getVendorFile(string $output_dir): File
    {
        return new File($output_dir . '/' . sprintf('%s.vendor.js', $this->file->getBaseName()));
    }
}

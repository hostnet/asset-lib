<?php
namespace Hostnet\Component\Resolver\Bundler;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\Dependency;

/**
 * Entry points are the starting files for your application. This can be main
 * files, stylesheets or javascript libraries.
 */
class EntryPoint
{
    private $file;
    private $bundle_files;
    private $vendor_files;
    private $asset_files;

    /**
     * @param File         $file
     * @param Dependency[] $dependencies
     */
    public function __construct(File $file, array $dependencies)
    {
        $this->file = $file;

        // Split the input files into bundle and vendor files.
        $this->bundle_files = [new Dependency($file)];
        $this->vendor_files = [];
        $this->asset_files = [];

        foreach ($dependencies as $dependency) {
            if ($dependency->isStatic()) {
                $this->asset_files[] = $dependency->getImport();
            } elseif (0 === strpos($dependency->getImport()->getPath(), 'node_modules/')) {
                $this->vendor_files[] = $dependency;
            } else {
                $this->bundle_files[] = $dependency;
            }
        }
    }

    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * @return Dependency[]
     */
    public function getBundleFiles(): array
    {
        return $this->bundle_files;
    }

    /**
     * @return Dependency[]
     */
    public function getVendorFiles(): array
    {
        return $this->vendor_files;
    }

    /**
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

    /**
     * Check if the entry point has vendor files.
     *
     * NOTE: stylesheets will never have these, since they are compiled to one
     * file.
     *
     * @return bool
     */
    public function hasVendorFile(): bool
    {
        return count($this->getVendorFiles()) > 0;
    }
}

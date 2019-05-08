<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\DependencyNodeInterface;

/**
 * An asset represent a since input file which needs to be written to the
 * source folder. Assets can contain multiple files but will always output one
 * file. Furthermore, assets will be outputted to the same path in the output
 * folder as they have in the source folder.
 *
 * For instance: sources/styles/layout.css will be outputted as web/styles/layout.css.
 */
final class Asset
{
    private $file;
    private $files;
    private $extension;

    /**
     * @param DependencyNodeInterface $file
     */
    public function __construct(DependencyNodeInterface $file)
    {
        $this->file  = $file->getFile();
        $this->files = [];

        $walker = new TreeWalker(function (DependencyNodeInterface $dependency): void {
            $this->files[] = $dependency;
        });

        $walker->walk($file);
    }

    /**
     * Return the file from which the asset needs to be created. This is the
     * source file.
     *
     * @return File
     */
    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * Return all files which are part of this asset.
     *
     * @return DependencyNodeInterface[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Return the asset file name. This is the target file name.
     *
     * @param string $output_folder
     * @param string $source_folder
     * @return File
     */
    public function getAssetFile(string $output_folder, string $source_folder): File
    {
        $base_dir = trim(substr($this->file->dir, \strlen($source_folder)), '/');

        if ($base_dir === '.') {
            $base_dir = '';
        }

        if (\strlen($base_dir) > 0) {
            $base_dir .= '/';
        }

        $ext = !empty($this->file->extension) ? '.' . $this->file->extension : '';

        return new File($output_folder . '/' . $base_dir . $this->file->getBaseName() . $ext);
    }
}

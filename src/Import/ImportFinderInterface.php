<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import;

use Hostnet\Component\Resolver\File;

/**
 * Implementation of this interface allow for finding of imports.
 */
interface ImportFinderInterface
{
    /**
     * Resolve the entire dependency tree of a file.
     *
     * @param File $file
     * @return RootFile
     */
    public function all(File $file): RootFile;
}

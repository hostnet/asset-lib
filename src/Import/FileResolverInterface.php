<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Import;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\Nodejs\Exception\FileNotFoundException;

/**
 * Implementations of this interface allows for file resolving of an imported
 * file to an actual file on disk.
 */
interface FileResolverInterface
{
    /**
     * Resolve a require from another file (parent) to a file.
     *
     * @param string $name
     * @param File   $parent
     * @throws FileNotFoundException when no file could be resolved.
     * @return Import
     */
    public function asRequire(string $name, File $parent): Import;
}

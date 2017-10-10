<?php
declare(strict_types=1);
/**
 * @copyright 2017 Hostnet B.V.
 */

namespace Hostnet\Component\Resolver\Bundler\Pipeline;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\ReaderInterface;
use Hostnet\Component\Resolver\Import\DependencyNodeInterface;

/**
 * The content pipeline allows for pushing items through it to be assets. Once
 * pushed, it will go through various processors until it is written to disk as
 * the given output file.
 */
interface ContentPipelineInterface
{
    /**
     * Peek an item through the content pipeline. This will return the
     * resulting file extension.
     *
     * @param File $input_file
     * @return string
     */
    public function peek(File $input_file): string;

    /**
     * Bundles a list of dependencies into a single string.
     *
     * When passing a $target_file in dev mode it will take the modified time
     * of that. It has no use otherwise.
     *
     * @param DependencyNodeInterface[] $dependencies
     * @param ReaderInterface $file_reader
     * @param File|null $target_file
     * @return string
     */
    public function push(array $dependencies, ReaderInterface $file_reader, File $target_file = null): string;
}

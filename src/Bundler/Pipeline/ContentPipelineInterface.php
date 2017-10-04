<?php

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
     * Push a bundled file on the pipeline with a list of dependencies.
     *
     * @param DependencyNodeInterface[] $dependencies
     * @param File                      $target_file
     * @param ReaderInterface           $file_reader
     * @return string
     */
    public function push(array $dependencies, File $target_file, ReaderInterface $file_reader): string;
}

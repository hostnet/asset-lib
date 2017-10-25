<?php
declare(strict_types=1);
/**
 * @copyright 2017 Hostnet B.V.
 */

namespace Hostnet\Component\Resolver\Bundler\Pipeline;

/**
 * The content pipeline allows for pushing items through it to be assets. Once
 * pushed, it will go through various processors until it is written to disk as
 * the given output file.
 */
interface MutableContentPipelineInterface extends ContentPipelineInterface
{
    public function addProcessor(ContentProcessorInterface $processor): void;
}

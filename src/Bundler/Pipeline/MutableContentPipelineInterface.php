<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler\Pipeline;

use Hostnet\Component\Resolver\Bundler\Processor\ContentProcessorInterface;

/**
 * The content pipeline allows for pushing items through it to be assets. Once
 * pushed, it will go through various processors until it is written to disk as
 * the given output file.
 */
interface MutableContentPipelineInterface extends ContentPipelineInterface
{
    /**
     * Add a processor to the content pipeline.
     *
     * @param ContentProcessorInterface $processor
     */
    public function addProcessor(ContentProcessorInterface $processor): void;
}

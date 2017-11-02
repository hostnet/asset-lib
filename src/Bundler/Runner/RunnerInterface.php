<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler\Runner;

use Hostnet\Component\Resolver\Bundler\ContentItem;

/**
 * For when you have to run some javascript process for a transition.
 */
interface RunnerInterface
{
    /**
     * @see RunnerType for potential types
     */
    public function execute(string $type, ContentItem $item): string;
}

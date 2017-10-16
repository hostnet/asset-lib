<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler\Processor;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Bundler\Pipeline\ContentProcessorInterface;
use Hostnet\Component\Resolver\Bundler\Runner\LessRunner;

/**
 * Process LESS files to CSS.
 */
final class LessContentProcessor implements ContentProcessorInterface
{
    private $less_runner;

    public function __construct(LessRunner $less_runner)
    {
        $this->less_runner = $less_runner;
    }

    public function supports(ContentState $state): bool
    {
        return $state->current() === ContentState::UNPROCESSED && $state->extension() === 'less';
    }

    public function peek(string $cwd, ContentState $state): void
    {
        $state->transition(ContentState::READY, 'css');
    }

    public function transpile(string $cwd, ContentItem $item): void
    {
        $output = $this->less_runner->execute($item, $cwd);
        $item->transition(ContentState::READY, $output, 'css');
    }
}

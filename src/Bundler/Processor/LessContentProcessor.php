<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler\Processor;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Bundler\Runner\RunnerInterface;
use Hostnet\Component\Resolver\Bundler\Runner\RunnerType;

/**
 * Process LESS files to CSS.
 */
final class LessContentProcessor implements ContentProcessorInterface
{
    private $runner;

    public function __construct(RunnerInterface $runner)
    {
        $this->runner = $runner;
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
        $output = $this->runner->execute(RunnerType::LESS, $item);
        $item->transition(ContentState::READY, $output, 'css');
    }
}

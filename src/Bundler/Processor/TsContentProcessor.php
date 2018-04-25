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
 * Processes TypeScript files to JavaScript.
 */
final class TsContentProcessor implements ContentProcessorInterface
{
    private $runner;

    public function __construct(RunnerInterface $runner)
    {
        $this->runner = $runner;
    }

    public function supports(ContentState $state): bool
    {
        return $state->current() === ContentState::UNPROCESSED && $state->extension() === 'ts';
    }

    public function peek(string $cwd, ContentState $state): void
    {
        $state->transition(ContentState::PROCESSED, 'js');
    }

    public function transpile(string $cwd, ContentItem $item): void
    {
        $module_name = preg_replace('/\.ts$/i', '', $item->module_name);

        $item->transition(
            ContentState::PROCESSED,
            $this->runner->execute(RunnerType::TYPE_SCRIPT, $item),
            'js',
            $module_name
        );
    }
}

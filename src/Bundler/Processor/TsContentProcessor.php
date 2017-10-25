<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler\Processor;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Bundler\Runner\TsRunner;

/**
 * Processes TypeScript files to JavaScript.
 */
final class TsContentProcessor implements ContentProcessorInterface
{
    private $ts_runner;

    public function __construct(TsRunner $ts_runner)
    {
        $this->ts_runner = $ts_runner;
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
        $module_name = $item->module_name;

        if (false !== ($i = strrpos($module_name, '.'))) {
            $module_name = substr($module_name, 0, $i);
        }

        $item->transition(
            ContentState::PROCESSED,
            $this->ts_runner->execute($item),
            'js',
            $module_name
        );
    }
}

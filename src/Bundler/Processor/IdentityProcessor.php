<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler\Processor;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Bundler\Pipeline\ContentProcessorInterface;

/**
 * This processor doesn't really do anything but change the state. This is
 * useful for files which need to be processed as is like HTML.
 */
final class IdentityProcessor implements ContentProcessorInterface
{
    private $extension;
    private $done_state;

    public function __construct(string $extension, string $done_state = ContentState::READY)
    {
        $this->extension  = $extension;
        $this->done_state = $done_state;
    }

    public function supports(ContentState $state): bool
    {
        return $state->current() === ContentState::UNPROCESSED && $this->extension === $state->extension();
    }

    public function peek(string $cwd, ContentState $state): void
    {
        $state->transition($this->done_state);
    }

    public function transpile(string $cwd, ContentItem $item): void
    {
        $item->transition($this->done_state);
    }
}

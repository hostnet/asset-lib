<?php
namespace Hostnet\Component\Resolver\Transpile\BuildIn;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Transpile\FileTranspilerInterface;

final class IdentityTranspiler implements FileTranspilerInterface
{
    private $extension;
    private $done_state;

    public function __construct(string $extension, string $done_state = ContentState::READY)
    {
        $this->extension = $extension;
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

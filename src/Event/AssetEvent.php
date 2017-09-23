<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Event;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Symfony\Component\EventDispatcher\Event;

/**
 * Asset events are thrown before and after a change to an asset. This can be
 * transpilation or writing it to disk.
 */
class AssetEvent extends Event
{
    private $item;

    public function __construct(ContentItem $item)
    {
        $this->item = $item;
    }

    public function getItem(): ContentItem
    {
        return $this->item;
    }

    public function setItem(ContentItem $item): void
    {
        $this->item = $item;
    }
}

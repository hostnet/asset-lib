<?php

namespace Hostnet\Component\Resolver\Bundler;


class PipelineDispatcher
{

    public function trigger(string $event, Item $item): void
    {
        var_dump([$event, $item->file->path]);
    }
}

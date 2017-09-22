<?php

namespace Hostnet\Component\Resolver\Bundler;


interface WriterInterface
{
    public function write(Item $item): void;
}

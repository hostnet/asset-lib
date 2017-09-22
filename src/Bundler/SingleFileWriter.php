<?php

namespace Hostnet\Component\Resolver\Bundler;


class SingleFileWriter implements WriterInterface
{
    private $output_file;
    private $dispatcher;

    public function __construct(string $output_file, PipelineDispatcher $dispatcher)
    {
        $this->output_file = $output_file;
        $this->dispatcher = $dispatcher;
    }

    public function write(Item $item): void
    {
        $this->dispatcher->trigger('PRE_WRITE', $item);

        file_put_contents($this->output_file, $item->getContent());

        $this->dispatcher->trigger('POST_WRITE', $item);
    }
}

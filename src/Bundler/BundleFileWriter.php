<?php

namespace Hostnet\Component\Resolver\Bundler;


class BundleFileWriter implements WriterInterface
{
    private $output_file;
    private $queue;
    private $dispatcher;
    private $buffer = '';

    public function __construct(string $output_file, array $queue, PipelineDispatcher $dispatcher)
    {
        $this->output_file = $output_file;
        $this->queue = $queue;
        $this->dispatcher = $dispatcher;
    }

    public function write(Item $item): void
    {
        if (!in_array($item, $this->queue, true)) {
            throw new \LogicException('File not part of queue');
        }

        // Append the buffer.
        $this->buffer .= $item->getContent();

        // Remove item from the queue.
        $this->queue = array_filter($this->queue, function (Item $i) use ($item) {
            return $i !== $item;
        });

        // Check if the queue is empty now.
        if (count($this->queue) === 0) {
            $this->dispatcher->trigger('PRE_WRITE', $item);

            file_put_contents($this->output_file, $this->buffer);

            $this->dispatcher->trigger('POST_WRITE', $item);
        }
    }
}

<?php

namespace Hostnet\Component\Resolver\Bundler;


use Hostnet\Component\Resolver\Transpile\Transpiler;

class Pipeline
{
    private $dispatcher;
    private $transpiler;
    private $wrapper;

    public function __construct(PipelineDispatcher $dispatcher, Transpiler $transpiler, JsModuleWrapperInterface $wrapper)
    {
        $this->dispatcher = $dispatcher;
        $this->transpiler = $transpiler;
        $this->wrapper = $wrapper;
    }

    public function process(Item $item, WriterInterface $writer): void
    {
        // Transition the item until it is in a ready state.
        while ($item->getState() !== Item::READY) {
            $this->next($item);
        }

        // Write
        $writer->write($item);
    }

    private function next(Item $item): void
    {
        switch ($item->getState()) {
            case Item::UNPROCESSED:
                $this->dispatcher->trigger('PRE_TRANSPILE', $item);

                $this->transpiler->transpile($item);

                $this->dispatcher->trigger('POST_TRANSPILE', $item);

                break;
            case Item::PROCESSED:
                $this->dispatcher->trigger('PRE_WRAP', $item);

                $this->wrapper->wrap($item);

                $this->dispatcher->trigger('POST_WRAP', $item);

                break;
            default:
                throw new \LogicException(sprintf('State "%s" cannot be transitioned.', $item->getState()));
        }
    }
}

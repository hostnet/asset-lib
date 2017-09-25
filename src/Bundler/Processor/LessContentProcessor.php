<?php
namespace Hostnet\Component\Resolver\Bundler\Processor;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Bundler\Pipeline\ContentProcessorInterface;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Hostnet\Component\Resolver\Bundler\TranspileException;
use Symfony\Component\Process\ProcessBuilder;

final class LessContentProcessor implements ContentProcessorInterface
{
    private $nodejs;

    public function __construct(Executable $nodejs)
    {
        $this->nodejs = $nodejs;
    }

    public function supports(ContentState $state): bool
    {
        return $state->current() === ContentState::UNPROCESSED && $state->extension() === 'less';
    }

    public function peek(string $cwd, ContentState $state): void
    {
        $state->transition(ContentState::READY, 'css');
    }

    public function transpile(string $cwd, ContentItem $item): void
    {
        $process = ProcessBuilder::create()
            ->add($this->nodejs->getBinary())
            ->add(__DIR__ . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'lessc.js')
            ->add($cwd . DIRECTORY_SEPARATOR . $item->file->path)
            ->setInput($item->getContent())
            ->setEnv('NODE_PATH', $this->nodejs->getNodeModulesLocation())
            ->getProcess();

        $process->run();

        if (!$process->isSuccessful()) {
            throw new TranspileException(
                sprintf('Cannot compile "%s" due to compiler error.', $item->file->path),
                $process->getOutput() . $process->getErrorOutput()
            );
        }

        $item->transition(ContentState::READY, $process->getOutput(), 'css');
    }
}

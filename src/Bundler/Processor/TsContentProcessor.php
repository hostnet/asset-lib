<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler\Processor;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Bundler\Pipeline\ContentProcessorInterface;
use Hostnet\Component\Resolver\Bundler\TranspileException;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Processes TypeScript files to JavaScript.
 */
final class TsContentProcessor implements ContentProcessorInterface
{
    private $nodejs;

    public function __construct(Executable $nodejs)
    {
        $this->nodejs = $nodejs;
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
        $process = ProcessBuilder::create()
            ->add($this->nodejs->getBinary())
            ->add(__DIR__ . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'tsc.js')
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

        $module_name = $item->module_name;

        if (false !== ($i = strrpos($module_name, '.'))) {
            $module_name = substr($module_name, 0, $i);
        }

        $item->transition(
            ContentState::PROCESSED,
            $process->getOutput(),
            'js',
            $module_name
        );
    }
}

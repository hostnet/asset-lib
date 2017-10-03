<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\EventListener;

use Hostnet\Component\Resolver\Bundler\TranspileException;
use Hostnet\Component\Resolver\Event\AssetEvent;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Symfony\Component\Process\ProcessBuilder;

class CleanCssListener
{
    private $nodejs;

    public function __construct(Executable $nodejs)
    {
        $this->nodejs = $nodejs;
    }

    /**
     * {@inheritdoc}
     */
    public function onPreWrite(AssetEvent $event): void
    {
        $item = $event->getItem();
        $file = $item->file;

        // Check if we need to apply the listener.
        if ($item->getState()->extension() !== 'css') {
            return;
        }

        $process = ProcessBuilder::create()
            ->add($this->nodejs->getBinary())
            ->add(__DIR__ . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'cleancss.js')
            ->setInput($item->getContent())
            ->setEnv('NODE_PATH', $this->nodejs->getNodeModulesLocation())
            ->getProcess();

        $process->run();

        if (!$process->isSuccessful()) {
            throw new TranspileException(
                sprintf('Cannot transform "%s" due to clean-css error.', $file->path),
                $process->getErrorOutput()
            );
        }

        // Keep the current state, but update the content.
        $item->transition($item->getState()->current(), $process->getOutput());
    }
}

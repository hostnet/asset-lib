<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler\Runner;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\TranspileException;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\File;
use Symfony\Component\Process\ProcessBuilder;

class LessRunner
{
    private $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    public function execute(ContentItem $item): string
    {
        $nodejs  = $this->config->getNodeJsExecutable();
        $process = ProcessBuilder::create()
            ->add($nodejs->getBinary())
            ->add(__DIR__ . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'lessc.js')
            ->add(File::makeAbsolutePath($item->file->path, $this->config->getProjectRoot()))
            ->setInput($item->getContent())
            ->setEnv('NODE_PATH', $nodejs->getNodeModulesLocation())
            ->getProcess();

        $process->run();

        if (!$process->isSuccessful()) {
            throw new TranspileException(
                sprintf('Cannot compile "%s" due to compiler error.', $item->file->path),
                $process->getOutput() . $process->getErrorOutput()
            );
        }

        return $process->getOutput();
    }
}

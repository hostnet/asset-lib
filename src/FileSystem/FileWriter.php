<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\FileSystem;

use Hostnet\Component\Resolver\Event\FileEvent;
use Hostnet\Component\Resolver\Event\FileEvents;
use Hostnet\Component\Resolver\File;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Implementation of the WriterInterface which writes it to disk.
 */
final class FileWriter implements WriterInterface
{
    private $cwd;
    private $dispatcher;

    public function __construct(string $cwd, EventDispatcherInterface $dispatcher)
    {
        $this->cwd        = $cwd;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param File   $file
     * @param string $content
     */
    public function write(File $file, string $content): void
    {
        $path = File::makeAbsolutePath($file->path, $this->cwd);

        $event = new FileEvent($file, $content);
        $this->dispatcher->dispatch(FileEvents::PRE_WRITE, $event);

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $event->getContent());

        $this->dispatcher->dispatch(FileEvents::POST_WRITE, $event);
    }
}

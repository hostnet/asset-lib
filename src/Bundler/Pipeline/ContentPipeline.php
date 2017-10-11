<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler\Pipeline;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Bundler\TreeWalker;
use Hostnet\Component\Resolver\Cache\Cache;
use Hostnet\Component\Resolver\ConfigInterface;
use Hostnet\Component\Resolver\Event\AssetEvent;
use Hostnet\Component\Resolver\Event\AssetEvents;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\ReaderInterface;
use Hostnet\Component\Resolver\FileSystem\StringReader;
use Hostnet\Component\Resolver\Import\DependencyNodeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The content pipeline allows for pushing items through it to be assets. Once
 * pushed, it will go through various processors until it is written to disk as
 * the given output file.
 */
final class ContentPipeline implements ContentPipelineInterface
{
    private $dispatcher;
    private $logger;
    private $config;

    /**
     * @var ContentProcessorInterface[]
     */
    private $processors;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        ConfigInterface $config
    ) {
        $this->dispatcher = $dispatcher;
        $this->logger     = $logger;
        $this->config     = $config;
        $this->processors = [];
    }

    /**
     * Add a processor to the content pipeline.
     *
     * @param ContentProcessorInterface $processor
     */
    public function addProcessor(ContentProcessorInterface $processor): void
    {
        $this->processors[] = $processor;
    }

    /**
     * {@inheritdoc}
     */
    public function peek(File $input_file): string
    {
        $item = new ContentState($input_file->extension);

        // Transition the item until it is in a ready state.
        while ($item->current() !== ContentState::READY) {
            $this->nextPeek($item);
        }

        return $item->extension();
    }

    /**
     * {@inheritdoc}
     */
    public function push(array $dependencies, ReaderInterface $file_reader, File $target_file = null): string
    {
        $name = $target_file ? $target_file->path : '';
        $this->logger->debug(' * Compiling target {name}', ['name' => $name]);

        $buffer = '';

        /* @var $dependency DependencyNodeInterface */
        foreach ($dependencies as $dependency) {
            if ($dependency->isInlineDependency()) {
                continue;
            }
            $file        = $dependency->getFile();
            $module_name = $file->getName();

            if (!empty($this->config->getSourceRoot())
                && false !== strpos($module_name, $this->config->getSourceRoot())
            ) {
                $base_dir = trim(substr($file->dir, strlen($this->config->getSourceRoot())), '/');

                if (strlen($base_dir) > 0) {
                    $base_dir .= '/';
                }

                $module_name = $base_dir . $file->getBaseName() . '.' . $file->extension;
            }

            $cache_key   = Cache::createFileCacheKey($file);
            $cache_file  = $this->config->getCacheDir() . '/' . $cache_key;
            $target_file = $target_file ?: new File($cache_file);

            if ($this->config->isDev()
                && file_exists($cache_file)
                && !$this->checkIfChanged($target_file, $dependency)
            ) {
                [$content, $extension] = unserialize(file_get_contents(
                    $cache_file
                ), []);

                $item = new ContentItem($file, $module_name, $file_reader);
                $item->transition(ContentState::READY, $content, $extension);

                $this->logger->debug('   - Emiting cached file for {name}', ['name' => $item->file->path]);
            } else {
                $item = new ContentItem($file, $module_name, $file_reader);
                // Transition the item until it is in a ready state.
                while (!$item->getState()->isReady()) {
                    $this->next($item);
                }

                if ($this->config->isDev()) {
                    // cache the contents of the item
                    file_put_contents(
                        $cache_file,
                        serialize([$item->getContent(), $item->getState()->extension()])
                    );
                }

                $this->logger->debug('   - Emiting compile file for {name}', ['name' => $item->file->path]);
            }

            // Write
            $buffer .= $item->getContent();
        }

        // Create an item for the file to write to disk.
        $item = new ContentItem($target_file, $target_file->getName(), new StringReader($buffer));

        $this->dispatcher->dispatch(AssetEvents::READY, new AssetEvent($item));

        return $item->getContent();
    }

    private function next(ContentItem $item): void
    {
        $current_state = $item->getState()->current();

        foreach ($this->processors as $processor) {
            if ($processor->supports($item->getState())) {
                $this->dispatcher->dispatch(AssetEvents::PRE_PROCESS, new AssetEvent($item));

                $processor->transpile($this->config->cwd(), $item);

                $this->dispatcher->dispatch(AssetEvents::POST_PROCESS, new AssetEvent($item));

                break;
            }
        }

        try {
            $this->validateState($current_state, $item->getState()->current());
        } catch (\LogicException $e) {
            throw new \LogicException(sprintf('Failed to compile resource "%s".', $item->module_name), 0, $e);
        }
    }

    private function nextPeek(ContentState $item): void
    {
        $current_state = $item->current();

        foreach ($this->processors as $processor) {
            if ($processor->supports($item)) {
                $processor->peek($this->config->cwd(), $item);

                break;
            }
        }

        $this->validateState($current_state, $item->current());
    }

    private function validateState(string $old_state, string $new_state): void
    {
        // Make sure we did a transition. If no change was made, that means we are in an infinite loop.
        if ($old_state === $new_state) {
            throw new \LogicException('State did not change, transition must occur.');
        }
    }

    private function checkIfChanged(File $output_file, DependencyNodeInterface $dependency)
    {
        $file_path = File::makeAbsolutePath($output_file->path, $this->config->cwd());
        $mtime     = file_exists($file_path) ? filemtime($file_path) : -1;

        if ($mtime === -1) {
            return true;
        }

        $files = [$dependency->getFile()->path];

        // Collect all inline dependencies, since if any of those changed we need to recompile.
        $walker = new TreeWalker(function (DependencyNodeInterface $d) use (&$files) {
            if (!$d->isInlineDependency()) {
                return false;
            }

            $files[] = $d->getFile()->path;
        });
        $walker->walk($dependency);

        // Check if any of them changed.
        foreach ($files as $path) {
            if ($mtime < filemtime(File::makeAbsolutePath($path, $this->config->cwd()))) {
                return true;
            }
        }

        return false;
    }
}

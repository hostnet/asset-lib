<?php

namespace Hostnet\Component\Resolver\Bundler\Pipeline;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\ConfigInterface;
use Hostnet\Component\Resolver\Event\AssetEvent;
use Hostnet\Component\Resolver\Event\AssetEvents;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\Dependency;
use Hostnet\Component\Resolver\Transpile\FileTranspilerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The content pipeline allows for pushing items through it to be assets. Once
 * pushed, it will go through various processors until it is written to disk as
 * the given output file.
 */
class ContentPipeline
{
    private $dispatcher;
    private $logger;
    private $config;

    /**
     * @var FileTranspilerInterface[]
     */
    private $processors;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        ConfigInterface $config
    ) {
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->config = $config;
        $this->processors = [];
    }

    public function addProcessor(FileTranspilerInterface $processor): void
    {
        $this->processors[] = $processor;
    }

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
     * Push a bundled file on the pipeline with a list of dependencies.
     *
     * @param Dependency[] $dependencies
     * @param File         $target_file
     * @param FileReader   $file_reader
     */
    public function push(array $dependencies, File $target_file, FileReader $file_reader): void
    {
        if ($this->config->isDev() && !$this->checkIfAnyChanged($target_file, $dependencies)) {
            $this->logger->debug(' * Target already up to date');
            return;
        }

        $this->logger->debug(' * Compiling target {name}', ['name' => $target_file->path]);

        /* @var $items ContentItem[] */
        $items = array_map(function (Dependency $d) use ($file_reader) {
            return new ContentItem($d->getFile(), $d->getFile()->getName(), $file_reader);
        }, array_filter($dependencies, function (Dependency $d) {
            return !$d->isVirtual();
        }));

        $buffer = '';

        foreach ($items as $item) {
            $cache_key = $this->createFileCacheKey($item->file);

            if ($this->config->isDev()
                && file_exists($this->config->getCacheDir() . '/' . $cache_key)
                && !$this->checkIfChanged($target_file, $item->file)
            ) {
                [$content, $extension] = unserialize(file_get_contents(
                    $this->config->getCacheDir() . '/' . $cache_key
                ), []);

                $item->transition(ContentState::READY, $content, $extension);

                $this->logger->debug('   - Emiting cached file for {name}', ['name' => $item->file->path]);
            } else {
                // Transition the item until it is in a ready state.
                while (!$item->getState()->isReady()) {
                    $this->next($item);
                }

                if ($this->config->isDev()) {
                    // cache the contents of the item
                    file_put_contents(
                        $this->config->getCacheDir() . '/' . $cache_key,
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

        $path = $this->config->cwd() . '/' . $target_file->path;

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $this->dispatcher->dispatch(AssetEvents::PRE_WRITE, new AssetEvent($item));

        file_put_contents($path, $item->getContent());

        $this->dispatcher->dispatch(AssetEvents::POST_WRITE, new AssetEvent($item));
    }

    /**
     * Transition the item.
     *
     * @param ContentItem $item
     */
    private function next(ContentItem $item): void
    {
        $current_state = $item->getState()->current();

        foreach ($this->processors as $processor) {
            if ($processor->supports($item->getState())) {
                $this->dispatcher->dispatch(AssetEvents::PRE_TRANSPILE, new AssetEvent($item));

                $processor->transpile($this->config->cwd(), $item);

                $this->dispatcher->dispatch(AssetEvents::POST_TRANSPILE, new AssetEvent($item));

                break;
            }
        }

        $this->validateState($current_state, $item->getState()->current());
    }

    /**
     * Transition the item.
     *
     * @param ContentState $item
     */
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

    /**
     * Check if the output file is newer than the input files.
     *
     * @param File         $output_file
     * @param Dependency[] $input_files
     * @return bool
     */
    private function checkIfAnyChanged(File $output_file, array $input_files): bool
    {
        // did the sources change?
        $sources_file = $this->config->getCacheDir() . '/' . $this->createFileCacheKey($output_file) . '.sources';
        $input_sources = array_map(function (Dependency $d) {
            return $d->getFile()->path;
        }, $input_files);

        sort($input_sources);

        if (!file_exists($sources_file)) {
            // make sure the cache dir exists
            if (!is_dir(dirname($sources_file))) {
                mkdir(dirname($sources_file), 0777, true);
            }
            file_put_contents($sources_file, serialize($input_sources));

            return true;
        }

        $sources = unserialize(file_get_contents($sources_file), []);

        if (count(array_diff($sources, $input_sources)) > 0 || count(array_diff($input_sources, $sources)) > 0) {
            file_put_contents($sources_file, serialize($input_sources));

            return true;
        }

        // Did the files change?
        $file_path = $this->config->cwd() . '/' . $output_file->path;
        $mtime = file_exists($file_path) ? filemtime($file_path) : -1;

        if ($mtime === -1) {
            return true;
        }

        foreach ($input_files as $input_file) {
            if ($mtime < filemtime($this->config->cwd() . '/' . $input_file->getFile()->path)) {
                return true;
            }
        }

        return false;
    }

    private function checkIfChanged(File $output_file, File $file)
    {
        $file_path = $this->config->cwd() . '/' . $output_file->path;
        $mtime = file_exists($file_path) ? filemtime($file_path) : -1;

        if ($mtime === -1) {
            return true;
        }

        return $mtime < filemtime($this->config->cwd() . '/' . $file->path);
    }

    /**
     * Create a cache key for a file. This must be unique for a file, but
     * always the same for each file and it's location. The same file in a
     * different folder should have a different key.
     *
     * @param File $output_file
     * @return string
     */
    private function createFileCacheKey(File $output_file): string
    {
        return substr(md5($output_file->path), 0, 5) . '_' . str_replace('/', '.', $output_file->path);
    }
}

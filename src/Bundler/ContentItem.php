<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\ReaderInterface;

/**
 * Content items represent a file which needs to be processed through the bundler.
 */
final class ContentItem
{
    /**
     * The original file for which this item is made.
     *
     * @var File
     */
    public $file;

    /**
     * Module name of the item. In most cases this is simply the file name.
     *
     * @var string
     */
    public $module_name;

    private $content;
    private $reader;
    private $state;

    public function __construct(File $file, string $module_name, ReaderInterface $reader)
    {
        $this->file        = $file;
        $this->module_name = $module_name;
        $this->reader      = $reader;
        $this->state       = new ContentState($this->file->extension);
    }

    public function getState(): ContentState
    {
        return $this->state;
    }

    public function getContent(): string
    {
        if (null === $this->content) {
            $this->content = $this->reader->read($this->file);
        }

        return $this->content;
    }

    /**
     * Transition the Content to a new (or the same) state.
     *
     * @param string      $state
     * @param string|null $new_content
     * @param string|null $new_extension
     * @param string|null $new_module_name
     */
    public function transition(
        string $state,
        string $new_content = null,
        string $new_extension = null,
        string $new_module_name = null
    ) {
        $this->state->transition($state, $new_extension);

        if (null !== $new_content) {
            $this->content = $new_content;
        }

        if (null !== $new_module_name) {
            $this->module_name = $new_module_name;
        }
    }
}

<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\ReaderInterface;

class ContentItem
{
    public $file;
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

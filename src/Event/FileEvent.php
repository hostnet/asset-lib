<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Event;

use Hostnet\Component\Resolver\File;
use Symfony\Component\EventDispatcher\Event;

/**
 * File events are thrown before and after file IO.
 */
class FileEvent extends Event
{
    private $file;
    private $content;

    public function __construct(File $file, string $content)
    {
        $this->file    = $file;
        $this->content = $content;
    }

    public function getFile(): File
    {
        return $this->file;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }
}

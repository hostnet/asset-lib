<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\FileSystem;

use Hostnet\Component\Resolver\File;

/**
 * Implementation of the ReaderInterface which reads the content from a string.
 * This ignores the file itself and just returns the content given in the
 * constructor.
 *
 * This is useful when you already know the content and do not want to use the
 * file system.
 */
final class StringReader implements ReaderInterface
{
    private $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function read(File $file): string
    {
        return $this->content;
    }
}

<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler\Pipeline;

use Hostnet\Component\Resolver\File;

class StringReader implements ReaderInterface
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

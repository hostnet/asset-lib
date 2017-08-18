<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver;

/**
 * Instances of this class represent a file on disk.
 */
class File
{
    public $path;
    public $extension;
    public $dir;

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->dir = dirname($path);
        $this->extension = pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Return the name of the import. This is usually the file or module name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->path;
    }

    /**
     * Return the name of the import without any extensions.
     *
     * @return string
     */
    public function getBaseName(): string
    {
        return basename($this->path, '.' . $this->extension);
    }

    /**
     * Check of another ImportInterface is equal to this.
     *
     * @param File $other
     * @return bool
     */
    public function equals(File $other): bool
    {
        return $this->path === $other->path;
    }

    /**
     * Return a cleaned path. This removed any ../ and ./ and replaces them
     * accordingly.
     *
     * @param string $path
     * @return string
     */
    public static function clean(string $path): string
    {
        $parts = explode('/', str_replace(['\\', '//'], '/', $path));

        $absolutes = [];
        foreach ($parts as $part) {
            if ('.' === $part) {
                continue;
            }

            if ('..' === $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }

        return implode('/', $absolutes);
    }
}

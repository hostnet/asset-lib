<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver;

/**
 * Instances of this class represent a file on disk.
 */
class File
{
    private $path;
    private $dir;
    private $ext;

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->dir = dirname($path);
        $this->ext = pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Return the name of the import. This is usually the file or module name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->getPath();
    }

    /**
     * Return the name of the import without any extensions.
     *
     * @return string
     */
    public function getBaseName(): string
    {
        return basename($this->path, '.' . $this->getExtension());
    }

    /**
     * Return the path of file to import.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Return the extension for the imported file.
     *
     * @return string
     */
    public function getExtension(): string
    {
        return $this->ext;
    }

    /**
     * Return the directory the file or module is located.
     *
     * @return string
     */
    public function getDirectory(): string
    {
        return $this->dir;
    }

    /**
     * Check of another ImportInterface is equal to this.
     *
     * @param File $other
     * @return bool
     */
    public function equals(File $other): bool
    {
        return $this->getPath() === $other->getPath();
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
        $parts = array_filter(explode('/', str_replace('\\', '/', $path)), 'strlen');

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
        return ($path[0] === '/' ? '/' : '') . implode('/', $absolutes);
    }
}

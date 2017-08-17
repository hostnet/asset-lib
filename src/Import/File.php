<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import;

/**
 * Instances of this class represent a file on disk.
 */
class File implements ImportInterface
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
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->getPath();
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseName(): string
    {
        return basename($this->path, '.' . $this->getExtension());
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getDirectory(): string
    {
        return $this->dir;
    }

    /**
     * {@inheritdoc}
     */
    public function equals(ImportInterface $other): bool
    {
        return $this->getPath() === $other->getPath();
    }

    /**
     * {@inheritdoc}
     */
    public function getExtension(): string
    {
        return $this->ext;
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
        $parts = array_filter(explode('/', $path), 'strlen');

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

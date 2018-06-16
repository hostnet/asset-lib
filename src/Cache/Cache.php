<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Cache;

use Hostnet\Component\Resolver\Cache\Exception\InvalidArgumentException;
use Hostnet\Component\Resolver\File;

final class Cache implements FileCacheInterface
{
    private $file;
    private $data;

    public function __construct(string $file = '.deps')
    {
        $this->file = $file;
        $this->data = [];
    }

    /**
     * Create a cache key for a file. This must be unique for a file, but
     * always the same for each file and it's location. The same file in a
     * different folder should have a different key.
     *
     * @param File $file
     * @return string
     */
    public static function createFileCacheKey(File $file): string
    {
        $hash = md5($file->path);

        return substr($hash, 0, 2) . '/' . substr($hash, 2, 5) . '_' . str_replace('/', '.', $file->path);
    }

    /**
     * {@inheritdoc}
     */
    public function has($key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->data[$key];
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        $this->data[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        if (!$this->has($key)) {
            throw new InvalidArgumentException();
        }

        unset($this->data[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->data = [];
    }

    /**
     * {@inheritdoc}
     */
    public function save(): void
    {
        file_put_contents($this->file, serialize($this->data));
    }

    /**
     * {@inheritdoc}
     */
    public function load(): void
    {
        if (!file_exists($this->file)) {
            return;
        }

        // @codingStandardsIgnoreStart
        if (false !== ($data = @unserialize(file_get_contents($this->file)))) {
            $this->data = $data;
        }
        // @codingStandardsIgnoreEnd
    }

    /**
     * {@inheritdoc}
     * @throws \BadMethodCallException
     */
    public function getMultiple($keys, $default = null)
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     * @throws \BadMethodCallException
     */
    public function setMultiple($values, $ttl = null)
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     * @throws \BadMethodCallException
     */
    public function deleteMultiple($keys)
    {
        throw new \BadMethodCallException('Not implemented');
    }
}

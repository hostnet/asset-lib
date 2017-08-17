<?php
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Cache;

use Hostnet\Component\Resolver\Cache\Exception\InvalidArgumentException;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Module;
use Psr\SimpleCache\CacheInterface;

final class Cache implements CacheInterface
{
    private $file;
    private $data;

    public function __construct(string $file = '.deps')
    {
        $this->file = $file;
        $this->data = [];
    }


    /**
     * {@inheritdoc}
     */
    public function has($key)
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

    public function save()
    {
        file_put_contents($this->file, serialize($this->data));
    }

    public function load()
    {
        if (!file_exists($this->file)) {
            return;
        }

        if (false !== ($data = @unserialize(file_get_contents($this->file), [File::class, Module::class]))) {
            $this->data = $data;
        }
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

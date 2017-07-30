<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import;

/**
 * Instances of this class represent a node module.
 */
class Module implements ImportInterface
{
    private $name;
    private $file;

    public function __construct(string $name, ImportInterface $file)
    {
        $this->name = $name;
        $this->file = $file;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Return the parent name of the module. For instance the module 'foo/bar'
     * will have 'foo' as a parent module.
     */
    public function getParentName(): string
    {
        return dirname($this->name);
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseName(): string
    {
        return $this->file->getBaseName();
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        return $this->file->getPath();
    }

    /**
     * {@inheritdoc}
     */
    public function getDirectory(): string
    {
        return $this->file->getDirectory();
    }

    /**
     * {@inheritdoc}
     */
    public function equals(ImportInterface $other): bool
    {
        return $this->file->equals($other);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtension(): string
    {
        return $this->file->getExtension();
    }
}

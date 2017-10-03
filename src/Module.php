<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver;

/**
 * Modules are imported files with an alias.
 */
class Module extends File
{
    private $name;

    public function __construct(string $name, string $path)
    {
        parent::__construct($path);

        $this->name = $name;
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
}

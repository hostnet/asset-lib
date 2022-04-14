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
        $dir_parts    = explode('/', $this->path);
        $module_parts = explode('/', $this->name);
        $parts        = [];

        for ($i = 0, $n = \count($dir_parts); $i < $n; $i++) {
            if ($dir_parts[$i] === $module_parts[0]) {
                break;
            }

            $parts[] = $dir_parts[$i];
        }

        if (empty($parts)) {
            return '';
        }

        return substr(\dirname($this->path), \strlen(implode('/', $parts)) + 1) ?: $this->name;
    }
}

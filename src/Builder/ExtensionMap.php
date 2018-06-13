<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

class ExtensionMap
{
    private $mapping;

    public function __construct(array $mapping)
    {
        $this->mapping = $mapping;
    }

    public function getResultingExtension(string $extension): string
    {
        if (!isset($this->mapping[$extension])) {
            throw new \RuntimeException("Cannot find resulting extension for \"{$extension}\".");
        }

        return $this->mapping[$extension];
    }
}

<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import\Nodejs;

/**
 * Data wrapper for the NodeJS executable information. This should contain the
 * path the node binary and the location of the node_modules folder.
 */
final class Executable
{
    private $node_bin;
    private $node_modules_location;

    public function __construct(string $node_bin, string $node_modules_location)
    {
        $this->node_bin              = $node_bin;
        $this->node_modules_location = $node_modules_location;
    }

    public function getBinary(): string
    {
        return $this->node_bin;
    }

    public function getNodeModulesLocation(): string
    {
        return $this->node_modules_location;
    }
}

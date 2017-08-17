<?php
namespace Hostnet\Component\Resolver\Import\Nodejs;

class Executable
{
    private $node_bin;
    private $node_modules_location;

    public function __construct(string $node_bin, string $node_modules_location)
    {
        $this->node_bin = $node_bin;
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

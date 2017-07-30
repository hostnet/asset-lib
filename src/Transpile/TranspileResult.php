<?php
namespace Hostnet\Component\Resolver\Transpile;

class TranspileResult
{
    private $module_name;
    private $content;

    public function __construct(string $module_name, string $content)
    {
        $this->module_name = $module_name;
        $this->content = $content;
    }

    public function getModuleName(): string
    {
        return $this->module_name;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}

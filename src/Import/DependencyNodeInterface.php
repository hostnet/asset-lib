<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import;

interface DependencyNodeInterface
{
    public function getChildren(): array;
}

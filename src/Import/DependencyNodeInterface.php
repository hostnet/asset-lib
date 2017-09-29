<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import;

use Hostnet\Component\Resolver\File;

interface DependencyNodeInterface
{
    public function getChildren(): array;

    public function getFile(): File;
}

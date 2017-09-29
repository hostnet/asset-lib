<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler;

use Hostnet\Component\Resolver\Import\DependencyNodeInterface;

class TreeWalker
{
    private $user_function;

    public function __construct(callable $user_function)
    {
        $this->user_function = $user_function;
    }

    public function walk(DependencyNodeInterface $node): void
    {
        foreach ($node->getChildren() as $child) {
            if (false !== call_user_func($this->user_function, $child)) {
                $this->walk($child);
            }
        }
    }
}

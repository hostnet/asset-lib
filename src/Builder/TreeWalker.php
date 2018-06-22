<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

use Hostnet\Component\Resolver\Import\DependencyNodeInterface;

/**
 * Recursively walk over a DependencyNodeInterface tree.
 */
final class TreeWalker
{
    private $user_function;

    /**
     * The $user_function will be called for each node and should have the
     * signature: function (DependencyNodeInterface $node) {}
     *
     * If the callable returns false, it will no longer traverse down that node.
     *
     * @param callable $user_function
     */
    public function __construct(callable $user_function)
    {
        $this->user_function = $user_function;
    }

    /**
     * Perform the walk over a node.
     *
     * @param DependencyNodeInterface $node
     */
    public function walk(DependencyNodeInterface $node): void
    {
        if (false === \call_user_func($this->user_function, $node)) {
            return;
        }

        $this->walkInternal($node);
    }

    private function walkInternal(DependencyNodeInterface $node): void
    {
        foreach ($node->getChildren() as $child) {
            if (false === \call_user_func($this->user_function, $child)) {
                continue;
            }

            $this->walkInternal($child);
        }
    }
}

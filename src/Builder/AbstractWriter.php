<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

abstract class AbstractWriter
{
    /**
     * Return the extension this step accepts. This should include the dot. I.e., ".css" or ".js".
     *
     * @return string
     */
    abstract public function acceptedExtension(): string;

    /**
     * Return the javascript module this step uses.
     *
     * @return string
     */
    abstract public function getJsModule(): string;

    /**
     * The lower the priority (i.e. 0 to 50) the later in the build process it
     * will be picked. The higher, the sooner. Default is 50.
     *
     * @return int
     */
    public function writePriority(): int
    {
        return 50;
    }

    /**
     * Calculate a unique hash for this step.
     *
     * @return string
     */
    final public function getHash(): string
    {
        return serialize([
            \get_class($this),
            $this->acceptedExtension(),
            $this->getJsModule(),
            $this->writePriority(),
        ]);
    }
}

<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

abstract class AbstractBuildStep
{
    public const FILE_READ = 0;
    public const FILE_TRANSPILED = 1;
    public const FILE_READY = 2;

    public const MODULES_COLLECTED = 3;
    public const MODULES_READY = 4;

    /**
     * Actions only applying to individual files
     */
    public const FILE_OPERATIONS = [
        self::FILE_READ,
        self::FILE_TRANSPILED,
        self::FILE_READY,
    ];

    /**
     * Actions only applying to a bundle of files (or a module)
     */
    public const MODULE_OPERATIONS = [
        self::MODULES_COLLECTED,
        self::MODULES_READY,
    ];

    /**
     * Return the states which this step accepts.
     *
     * @return array
     */
    abstract public function acceptedStates(): array;

    /**
     * Return the state which this step results in. This cannot be before any of accepted states.
     *
     * @return int
     */
    abstract public function resultingState(): int;

    /**
     * Return the extension this step accepts. This should include the dot. I.e., ".css" or ".js".
     *
     * @return string
     */
    abstract public function acceptedExtension(): string;

    /**
     * Return the extension this step results in. This should include the dot. I.e., ".css" or ".js".
     *
     * @return string
     */
    abstract public function resultingExtension(): string;

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
    public function buildPriority(): int
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
            $this->acceptedStates(),
            $this->resultingState(),
            $this->acceptedExtension(),
            $this->resultingExtension(),
            $this->getJsModule(),
            $this->buildPriority(),
        ]);
    }
}

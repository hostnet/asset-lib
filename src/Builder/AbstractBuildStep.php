<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

/**
 * Each build step represent a processing action for either a file or a module. This can be converting typescript to
 * javascript, uglification or anything other that needs to be done to a file before it can be outputted.
 *
 * There are two types of build steps: file and module. The distinction is based on the acceptedStates of the step.
 * States which are in the FILE_OPERATIONS are file build steps and those in the MODULE_OPERATIONS are module build
 * steps.
 *
 * Each step can have different accepting states, for instance: minification can happen for any module state since it
 * doesn't really care about when it happens. However, there can always be only one resulting state.
 *
 * To prevent infinite build loops transitions need to occur in the order FILE_READ -> FILE_TRANSPILED -> FILE_READY
 * and for modules MODULES_COLLECTED -> MODULES_READY. This means that the resulting state cannot transition back to
 * one of the previous states (self loops are okay). Finally, each step must have an accepting file extension and a
 * resulting one.
 *
 * There is also a build priority, this can be used to indicate a the order in which steps need to be performed if
 * multiple build can be taken. For instance, you might want to perform some module transformation over minification.
 * The lower the priority the later it will be executed and the higher the sooner.
 */
abstract class AbstractBuildStep
{
    /**
     * State indicating the file has been read from disk. From here the file must be transpiled or otherwise
     * transitioned into a ready state.
     */
    public const FILE_READ = 0;

    /**
     * State indicating that the file has been transpiled and is ready to be further transitioned into a ready state.
     */
    public const FILE_TRANSPILED = 1;

    /**
     * State indicating that the file is ready to be bundled into a module.
     */
    public const FILE_READY = 2;

    /**
     * State indicating that the files for the module have been collected and concatenated into a single module.
     */
    public const MODULES_COLLECTED = 3;

    /**
     * State indicating that the module is ready to be written to disk.
     */
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
     */
    abstract public function resultingState(): int;

    /**
     * Return the extension this step accepts. This should include the dot. I.e., ".css" or ".js".
     */
    abstract public function acceptedExtension(): string;

    /**
     * Return the extension this step results in. This should include the dot. I.e., ".css" or ".js".
     */
    abstract public function resultingExtension(): string;

    /**
     * Return the javascript module this step uses. This can be either a node modules like "@acme/my-step" or a
     * absolute path to a file like "/some/dir/acme/my-step.js"
     */
    abstract public function getJsModule(): string;

    /**
     * The lower the priority (i.e. 0 to 50) the later in the build process it
     * will be picked. The higher, the sooner. Default is 50.
     */
    public function buildPriority(): int
    {
        return 50;
    }

    /**
     * Calculate a unique hash for this step.
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

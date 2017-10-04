<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler;

final class ContentState
{
    /**
     * The READY state tells the bundler the file can be written to disk.
     */
    const READY = 'ready';

    /**
     * The UNPROCESSED state tells the bundler the file requires processing.
     */
    const UNPROCESSED = 'unprocessed';

    /**
     * The PROCESSED state tells the bundler the file has been through
     * processing and can be made ready.
     */
    const PROCESSED = 'processed';

    /**
     * Order of transitions, state should always be going down in this list.
     */
    const TRANSITION_ORDER = [
        self::UNPROCESSED,
        self::PROCESSED,
        self::READY,
    ];

    private $state;
    private $extension;

    public function __construct(string $extension, string $state = self::UNPROCESSED)
    {
        $this->extension = $extension;
        $this->state     = $state;
    }

    /**
     * Return the current state.
     *
     * @return string
     */
    public function current(): string
    {
        return $this->state;
    }

    /**
     * Check if we are in a READY state. This is equivalent to doing:
     * $state->current() === ContentState::READY
     *
     * @return bool
     */
    public function isReady(): bool
    {
        return $this->state === self::READY;
    }

    /**
     * Return the current file extension. This can change when transitioning to
     * a different state.
     *
     * @return string
     */
    public function extension(): string
    {
        return $this->extension;
    }

    /**
     * Transition to a different (or the same) state. This can also update the
     * extension.
     *
     * @param string      $state
     * @param string|null $new_extension
     * @throws \LogicException When transitioning backwards.
     */
    public function transition(string $state, string $new_extension = null)
    {
        $i = array_search($this->state, self::TRANSITION_ORDER, true);
        $j = array_search($state, self::TRANSITION_ORDER, true);

        if ($i > $j) {
            throw new \LogicException(
                sprintf('Cannot transition (from %s, to: %s) backwards.', $i, $j)
            );
        }

        $this->state = $state;

        if (null !== $new_extension) {
            $this->extension = $new_extension;
        }
    }
}

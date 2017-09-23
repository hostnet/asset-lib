<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler;


class ContentState
{
    const READY = 'ready';
    const UNPROCESSED = 'unprocessed';
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
        $this->state = $state;
    }

    public function current(): string
    {
        return $this->state;
    }

    public function extension(): string
    {
        return $this->extension;
    }

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

    public function isReady(): bool
    {
        return $this->state === self::READY;
    }
}

<?php

namespace Hostnet\Component\Resolver\Bundler;


use Hostnet\Component\Resolver\File;

class Item
{
    public const UNPROCESSED = 'unprocessed';
    public const PROCESSED   = 'processed';
    public const READY       = 'ready';

    /**
     * Order of transitions, state should always be going down in this list.
     */
    private const TRANSITION_ORDER = [
        self::UNPROCESSED,
        self::PROCESSED,
        self::READY,
    ];

    public $file;
    public $module_name;

    private $state = self::UNPROCESSED;
    private $content;

    public function __construct(File $file, string $module_name, string $content)
    {
        $this->file = $file;
        $this->module_name = $module_name;
        $this->content = $content;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function transition(string $state, string $new_content = null)
    {
        $i = array_search($this->state, self::TRANSITION_ORDER, true);
        $j = array_search($state, self::TRANSITION_ORDER, true);

        if ($i >= $j) {
            throw new \LogicException(
                sprintf('Cannot transition (from %s, to: %s) backwards or to same state.', $i, $j)
            );
        }

        $this->state = $state;

        if (null !== $new_content) {
            $this->content = $new_content;
        }
    }
}

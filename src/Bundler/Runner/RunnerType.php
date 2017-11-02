<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler\Runner;

/**
 * Every built in transition.
 *
 * Values are all three characters due to the protocol of the unix socket.
 */
final class RunnerType
{
    /**
     * @see http://typescriptlang.org/
     */
    public const TYPE_SCRIPT = 'TSC';

    /**
     * @see http://lesscss.org/
     */
    public const LESS = 'LES';

    /**
     * @see http://lisperator.net/uglifyjs/
     */
    public const UGLIFY = 'UGL';

    /**
     * @see https://github.com/jakubpawlowicz/clean-css
     */
    public const CLEAN_CSS = 'CLE';

    /**
     * @codeCoverageIgnore private by design because this is an ENUM class
     */
    private function __construct()
    {
    }
}

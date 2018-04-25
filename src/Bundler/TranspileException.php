<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler;

/**
 * Exception thrown when transpiling has failed.
 */
final class TranspileException extends \RuntimeException
{
    private $error_output;

    public function __construct(string $message, string $error_output, \Throwable $previous = null)
    {
        parent::__construct($message . ' Error: ' . $error_output, 0, $previous);

        $this->error_output = $error_output;
    }

    /**
     * Return the error output.
     *
     * @return string
     */
    public function getErrorOutput(): string
    {
        return $this->error_output;
    }
}

<?php
namespace Hostnet\Component\Resolver\Transpile;

use Throwable;

/**
 * Exception thrown when transpiling has failed.
 */
class TranspileException extends \RuntimeException
{
    private $transpiler_output;

    public function __construct(string $message, string $transpiler_output, Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);

        $this->transpiler_output = $transpiler_output;
    }

    public function getTranspilerOutput(): string
    {
        return $this->transpiler_output;
    }
}

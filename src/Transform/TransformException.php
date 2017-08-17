<?php

namespace Hostnet\Component\Resolver\Transform;


class TransformException extends \RuntimeException
{
    private $error_output;

    public function __construct(string $message, string $error_output, \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);

        $this->error_output = $error_output;
    }

    public function getErrorOutput(): string
    {
        return $this->error_output;
    }
}

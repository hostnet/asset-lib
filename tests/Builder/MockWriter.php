<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

class MockWriter extends AbstractWriter
{
    public function acceptedExtension(): string
    {
        return '.js';
    }

    public function getJsModule(): string
    {
        return 'phpunit';
    }
}

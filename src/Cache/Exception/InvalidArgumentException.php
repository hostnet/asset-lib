<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Cache\Exception;

use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;

/**
 * Exception thrown when the cache key string is not a legal value.
 */
final class InvalidArgumentException extends \InvalidArgumentException implements PsrInvalidArgumentException
{
}

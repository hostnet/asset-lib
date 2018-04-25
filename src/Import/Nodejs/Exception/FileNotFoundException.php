<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import\Nodejs\Exception;

/**
 * Exception thrown when trying to resolve a file but it could not be found.
 */
final class FileNotFoundException extends \RuntimeException
{
}

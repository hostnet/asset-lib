<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Report;

final class FileSizeHelper
{
    /**
     * Return a pretty format for file sizes. So for instance 54457 becomes:
     *
     * @param int $size
     * @return string
     * @throws \LogicException
     */
    public static function size(int $size): string
    {
        if ($size < 0) {
            return 'N/A';
        }
        if ($size === 0) {
            return '0 b';
        }

        $base     = log($size, 1024);
        $suffixes = ['b', 'kb', 'mb', 'gb'];

        if ($base >= 4) {
            throw new \LogicException('Size too large to format...');
        }

        return round(1024 ** ($base - floor($base))) . ' ' . $suffixes[(int) floor($base)];
    }
}

<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Report\Helper;

final class FileSizeHelper implements FileSizeHelperInterface
{
    public function filesize(string $file): int
    {
        return file_exists($file) ? filesize($file) : -1;
    }

    public function format(int $size): string
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

<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Report\Helper;

interface FileSizeHelperInterface
{
    /**
     * Return the file size of a file.
     *
     * @param string $file
     */
    public function filesize(string $file): int;

    /**
     * Return a pretty format for file sizes. So for instance 54457 becomes:
     *
     * @param int $size
     * @throws \LogicException
     */
    public function format(int $size): string;
}

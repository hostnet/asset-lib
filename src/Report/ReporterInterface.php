<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Report;

use Hostnet\Component\Resolver\File;

interface ReporterInterface
{
    public const STATE_UP_TO_DATE = 'up-to-date';
    public const STATE_FROM_CACHE = 'from-cache';
    public const STATE_BUILT      = 'built';
    public const STATE_INLINE     = 'inline';

    /**
     * Report an output file.
     *
     * @param File $file
     */
    public function reportOutputFile(File $file): void;

    /**
     * Report the dependencies for the given file.
     *
     * @param File  $file
     * @param array $dependencies
     */
    public function reportFileDependencies(File $file, array $dependencies): void;

    /**
     * Report a file state.
     *
     * @param File   $file
     * @param string $state
     */
    public function reportFileState(File $file, string $state): void;

    /**
     * Report the outputted file content.
     *
     * @param File   $file
     * @param string $content
     */
    public function reportFileContent(File $file, string $content): void;
}

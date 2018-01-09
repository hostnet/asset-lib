<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Report;

use Hostnet\Component\Resolver\File;

/**
 * Reporter does nothing.
 */
final class NullReporter implements ReporterInterface
{
    public function reportOutputFile(File $file): void
    {
    }

    public function reportFileDependencies(File $file, array $dependencies): void
    {
    }

    public function reportFileState(File $file, string $state): void
    {
    }

    public function reportFileContent(File $file, string $content): void
    {
    }
}

<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Report;

use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\File;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Reporter which outputs directly to the OutputInterface when a new output
 * file has been reported.
 *
 * This will mimic a logger and can be used for deployment output.
 */
final class ConsoleLoggingReporter implements ReporterInterface
{
    private $config;
    private $console_output;

    public function __construct(ConfigInterface $config, OutputInterface $console_output)
    {
        $this->config = $config;
        $this->console_output = $console_output;
    }

    public function reportOutputFile(File $file): void
    {
        $file_size = filesize(
            File::isAbsolutePath($file->path)
                ? $file->path
                : $this->config->getProjectRoot() . '/' . $file->path
        );

        $this->console_output->writeln(
            sprintf('Outputting "%s" <fg=yellow>%s</>.', $file->path, FileSizeHelper::size($file_size))
        );
    }

    public function reportFileDependencies(File $file, array $dependencies): void
    {
    }

    public function reportFileState(File $file, string $state): void
    {
    }

    public function reportFileSize(File $file, int $size): void
    {
    }
}

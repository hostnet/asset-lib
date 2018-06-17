<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Report;

use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Report\Helper\FileSizeHelperInterface;
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
    private $size_helper;
    private $build_files = [];

    public function __construct(ConfigInterface $config, OutputInterface $console_output, FileSizeHelperInterface $size_helper)
    {
        $this->config         = $config;
        $this->console_output = $console_output;
        $this->size_helper = $size_helper;
    }

    public function reportOutputFile(File $file): void
    {
        if (!\in_array($file->path, $this->build_files, true)) {
            return;
        }

        $file_size = $this->size_helper->filesize(
            File::isAbsolutePath($file->path)
                ? $file->path
                : $this->config->getProjectRoot() . '/' . $file->path
        );

        $this->console_output->writeln(
            sprintf('Outputting "%s" <fg=yellow>%s</>.', $file->path, $this->size_helper->format($file_size))
        );
    }

    public function reportFileDependencies(File $file, array $dependencies): void
    {
    }

    public function reportFileState(File $file, string $state): void
    {
        if ($state !== ReporterInterface::STATE_BUILT) {
            return;
        }

        $this->build_files[] = $file->path;
    }

    public function reportFileContent(File $file, string $content): void
    {
    }
}

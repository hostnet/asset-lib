<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Report;

use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\DependencyNodeInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Report which is able to aggregate all reported data in a human readable
 * format and print it the the console output.
 */
final class ConsoleReporter implements ReporterInterface
{
    private $config;
    private $with_reasons;

    private $file_sizes = [];
    private $file_states = [];
    private $output_file = [];
    private $dependencies = [];

    public function __construct(ConfigInterface $config, bool $with_reasons = false)
    {
        $this->config       = $config;
        $this->with_reasons = $with_reasons;
    }

    public function reportOutputFile(File $file): void
    {
        $this->output_file[] = $file->path;
    }

    public function reportFileDependencies(File $file, array $dependencies): void
    {
        $this->dependencies[$file->path] = $dependencies;
    }

    public function reportFileState(File $file, string $state): void
    {
        $this->file_states[$file->path] = $state;
    }

    public function reportFileSize(File $file, int $size): void
    {
        $this->file_sizes[$file->path] = $size;
    }

    public function printReport(OutputInterface $output): void
    {
        $table = new Table($output);
        $table->setStyle('compact');
        $table->getStyle()->setCellHeaderFormat('%s');
        $table->setHeaders(['Asset', 'Size', 'Status']);
        $table->setColumnStyle(0, (clone $table->getStyle())->setPadType(STR_PAD_LEFT));
        $table->setColumnStyle(1, (clone $table->getStyle())->setPadType(STR_PAD_BOTH));
        $table->setColumnWidth(1, 5);

        foreach ($this->output_file as $file) {
            $file_size = filesize(File::isAbsolutePath($file) ? $file : $this->config->getProjectRoot() . '/' . $file);
            $input_size = isset($this->file_sizes[$file]) ? FileSizeHelper::size($this->file_sizes[$file]) : '';
            $output_size = FileSizeHelper::size($file_size);

            $table->addRow([
                $file,
                (!empty($input_size)
                    ? ('<fg=yellow>' . $input_size . '</> -> ')
                    : ''
                ) . '<fg=yellow>' . $output_size . '</>',
                '<fg=green>[' . $this->file_states[$file] . ']</>'
            ]);
        }
        $table->render();

        $output->writeln('');

        $table = new Table($output);
        $table->setStyle('compact');
        $table->setColumnStyle(0, (clone $table->getStyle())->setPadType(STR_PAD_LEFT));

        foreach ($this->getFullDependencyList() as $i => [$file, $reasons]) {
            $file_size = filesize(File::isAbsolutePath($file) ? $file : $this->config->getProjectRoot() . '/' . $file);
            $size      = FileSizeHelper::size($file_size);

            $table->addRow([
                "[$i] ",
                $file . ' <fg=yellow>' . $size . '</> <fg=green>[' . ($this->file_states[$file] ?? 'N/A') . ']</>'
            ]);
            if ($this->with_reasons) {
                foreach ($reasons as $reason) {
                    $table->addRow(['', ' -> ' . $reason]);
                }
            }
        }
        $table->render();
    }

    private function getFullDependencyList(): array
    {
        if (empty($this->dependencies)) {
            return [];
        }

        $files = array_merge(...array_values($this->dependencies));
        $names = array_values(array_unique(array_map(function (DependencyNodeInterface $dep) {
            return $dep->getFile()->path;
        }, $files)));
        $reasons = array_combine($names, array_fill(0, count($names), []));

        foreach ($this->dependencies as $dependencies) {
            foreach ($dependencies as $dependency) {
                foreach ($dependency->getChildren() as $child) {
                    $file = $dependency->getFile()->path;

                    $reasons[$child->getFile()->path][] = sprintf(
                        'Included by [%d] <fg=cyan>%s</>',
                        array_search($file, $names, true),
                        $file
                    );
                }
            }
        }

        return array_map(function (string $file, array $reasons) {
            return [$file, $reasons];
        }, $names, $reasons);
    }
}

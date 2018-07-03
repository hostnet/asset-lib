<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Report;

use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\DependencyNodeInterface;
use Hostnet\Component\Resolver\Report\Helper\FileSizeHelperInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Report which is able to aggregate all reported data in a human readable
 * format and print it the the console output.
 */
final class ConsoleReporter implements ReporterInterface
{
    private $config;
    private $size_helper;
    private $with_reasons;

    private $file_sizes  = [];
    private $file_states = [];
    private $output_file = [];
    private $child_files = [];

    /**
     * @var DependencyNodeInterface[][]
     */
    private $dependencies = [];

    public function __construct(
        ConfigInterface $config,
        FileSizeHelperInterface $size_helper,
        bool $with_reasons = false
    ) {
        $this->config       = $config;
        $this->size_helper  = $size_helper;
        $this->with_reasons = $with_reasons;
    }

    public function reportOutputFile(File $file): void
    {
        $this->output_file[] = $this->makeRelativeToRoot($file);
    }

    public function reportChildOutputFile(File $file, File $parent): void
    {
        $this->child_files[$this->makeRelativeToRoot($file)] = $this->makeRelativeToRoot($parent);
    }

    public function reportFileDependencies(File $file, array $dependencies): void
    {
        $this->dependencies[$this->makeRelativeToRoot($file)] = $dependencies;
    }

    public function reportFileState(File $file, string $state): void
    {
        $this->file_states[$this->makeRelativeToRoot($file)] = $state;
    }

    public function reportFileContent(File $file, string $content): void
    {
        $this->file_sizes[$this->makeRelativeToRoot($file)] = \strlen($content);
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
            $file_size    = $this->size_helper->filesize(
                File::isAbsolutePath($file) ? $file : $this->config->getProjectRoot() . '/' . $file
            );
            $input_size   = isset($this->file_sizes[$file]) ? $this->size_helper->format($this->file_sizes[$file]) : '';
            $output_size  = $this->size_helper->format($file_size);
            $child_prefix = isset($this->child_files[$file]) ? '<fg=yellow>*</>' : '';

            $table->addRow([
                $child_prefix . $file,
                (!empty($input_size)
                    ? ('<fg=yellow>' . $input_size . '</> -> ')
                    : ''
                ) . '<fg=yellow>' . $output_size . '</>',
                '<fg=green>[' . $this->file_states[$file] . ']</>',
            ]);
        }
        $table->render();

        $output->writeln('');

        $table = new Table($output);
        $table->setStyle('compact');
        $table->setColumnStyle(0, (clone $table->getStyle())->setPadType(STR_PAD_LEFT));

        foreach ($this->getFullDependencyList() as $i => [$file, $reasons]) {
            $file_size = $this->size_helper->filesize(
                File::isAbsolutePath($file) ? $file : $this->config->getProjectRoot() . '/' . $file
            );
            $size      = $this->size_helper->format($file_size);

            $table->addRow([
                "[$i] ",
                $file . ' <fg=yellow>' . $size . '</> <fg=green>[' . ($this->file_states[$file] ?? 'N/A') . ']</>',
            ]);
            if (!$this->with_reasons) {
                continue;
            }

            foreach ($reasons as $reason) {
                $table->addRow(['', ' -> ' . $reason]);
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
            return $this->makeRelativeToRoot($dep->getFile());
        }, $files)));
        $reasons = array_combine($names, array_fill(0, count($names), []));

        foreach ($this->dependencies as $dependencies) {
            foreach ($dependencies as $dependency) {
                foreach ($dependency->getChildren() as $child) {
                    $file = $this->makeRelativeToRoot($dependency->getFile());

                    $reasons[$this->makeRelativeToRoot($child->getFile())][] = sprintf(
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

    private function makeRelativeToRoot(File $file): string
    {
        // make the output file relative to the output folder
        $path = File::clean($file->path);
        $output_dir = $this->config->getProjectRoot() . '/';

        if (false !== strpos($path, $output_dir)) {
            $path = substr($path, \strlen($output_dir));
        }

        return $path;
    }
}

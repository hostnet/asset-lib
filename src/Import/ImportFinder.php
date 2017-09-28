<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import;
use Hostnet\Component\Resolver\File;

/**
 * Import finder which uses ImportCollectorInterface to find imports.
 */
class ImportFinder implements ImportFinderInterface
{
    /**
     * @var ImportCollectorInterface[]
     */
    private $import_collectors = [];

    private $cwd;

    public function __construct(string $cwd)
    {
        $this->cwd = $cwd;
    }

    /**
     * @param File $file
     * @return Dependency[]
     */
    private function get(File $file): array
    {
        $imports = $this->findImports($file);
        $results = [];

        foreach ($imports->getImports() as $import) {
            $results[$import->getAs()] = [$import->getImportedFile(), $import->isVirtual()];
        }
        foreach ($imports->getResources() as $import) {
            $results[$import->path] = [$import, false, true];
        }
        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function all(File $file): array
    {
        $files = [];
        $queue = $this->get($file);

        $seen = array_values(array_map(function(array $d) {
            return $d[0]->path;
        }, $queue));

        while (count($queue) > 0) {
            $dep = array_shift($queue);
            $files[] = $dep;

            $imports = $this->findImports($dep[0]);

            foreach ($imports->getImports() as $import) {
                if (!in_array($import->getImportedFile()->path, $seen, true)) {
                    $queue[] = [$import->getImportedFile(), $import->isVirtual()];
                    $seen[] = $import->getImportedFile()->path;
                }
            }
            foreach ($imports->getResources() as $import) {
                if (!in_array($import->path, $seen, true)) {
                    $queue[] = [$import, false, true];
                    $seen[] = $import->path;
                }
            }
        }

        return $this->toTree($files);
    }

    /**
     * Add a collector the the finder.
     *
     * @param ImportCollectorInterface $import_collector
     */
    public function addCollector(ImportCollectorInterface $import_collector)
    {
        $this->import_collectors[] = $import_collector;
    }

    /**
     * Return all imports for a given file as a generator.
     *
     * @param File $file
     * @return ImportCollection
     */
    private function findImports(File $file): ImportCollection
    {
        $imports = new ImportCollection();

        foreach ($this->import_collectors as $collector) {
            if ($collector->supports($file)) {
                $collector->collect($this->cwd, $file, $imports);
            }
        }

        return $imports;
    }

    /**
     * @param Dependency[] $files
     * @return Dependency[]
     */
    private function toTree(array $files): array
    {
        return $files;
    }
}

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
     * {@inheritdoc}
     */
    public function get(File $file): array
    {
        $imports = $this->findImports($file);
        $results = [];

        foreach ($imports->getImports() as $import) {
            $results[$import->getAs()] = new Dependency($import->getImportedFile(), $import->isVirtual());
        }
        foreach ($imports->getResources() as $import) {
            $results[$import->getPath()] = new Dependency($import, false, true);
        }
        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function all(File $file): array
    {
        /* @var Dependency[] $queue */
        $queue = $this->get($file);
        $files = [];

        while (count($queue) > 0) {
            $dep = array_shift($queue);
            $files[] = $dep;

            $imports = $this->findImports($dep->getFile());

            foreach ($imports->getImports() as $import) {
                if (!$this->inArray($import->getImportedFile(), $queue, $files)) {
                    $queue[] = new Dependency($import->getImportedFile(), $import->isVirtual());
                }
            }
            foreach ($imports->getResources() as $import) {
                if (!$this->inArray($import, $queue, $files)) {
                    $queue[] = new Dependency($import, false, true);
                }
            }
        }

        return $files;
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
     * @param File                   $file
     * @param array[]|Dependency[][] ...$lists
     * @return bool
     */
    private function inArray(File $file, array ...$lists): bool
    {
        foreach ($lists as $list) {
            foreach ($list as $f) {
                if ($file->equals($f->getFile())) {
                    return true;
                }
            }
        }

        return false;
    }
}

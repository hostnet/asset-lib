<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import;

use Hostnet\Component\Resolver\File;

/**
 * Import finder which uses ImportCollectorInterface to find imports.
 */
final class ImportFinder implements MutableImportFinderInterface
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

    public function addCollector(ImportCollectorInterface $import_collector): void
    {
        $this->import_collectors[] = $import_collector;
    }

    public function all(File $file): RootFile
    {
        $files = [];
        $queue = $this->get($file);

        $seen = array_combine(array_values(array_map(function (array $d) {
            return $d[0]->path;
        }, $queue)), array_fill(0, \count($queue), true));

        while (\count($queue) > 0) {
            $dep     = array_shift($queue);
            $files[] = $dep;

            $imports = $this->findImports($dep[0]);

            foreach ($imports->getImports() as $import) {
                $imported_file = $import->import;

                if (isset($seen[$imported_file->path])) {
                    continue;
                }

                $queue[] = [$imported_file, $dep[0], $import->virtual, false];

                $seen[$imported_file->path] = true;
            }

            foreach ($imports->getResources() as $import) {
                if (isset($seen[$import->path])) {
                    continue;
                }

                $queue[]             = [$import, $dep[0], false, true];
                $seen[$import->path] = true;
            }
        }

        return $this->toTree($file, $files);
    }

    /**
     * @param File $file
     * @return DependencyNodeInterface[]
     */
    private function get(File $file): array
    {
        $imports = $this->findImports($file);
        $results = [];

        foreach ($imports->getImports() as $import) {
            $results[$import->as] = [$import->import, $file, $import->virtual, false];
        }
        foreach ($imports->getResources() as $import) {
            $results[$import->path] = [$import, $file, false, true];
        }
        return $results;
    }

    /**
     * Return all imports for a given file as a generator.
     *
     * @param File $file
     */
    private function findImports(File $file): ImportCollection
    {
        $imports = new ImportCollection();

        foreach ($this->import_collectors as $collector) {
            if (!$collector->supports($file)) {
                continue;
            }

            $collector->collect($this->cwd, $file, $imports);
        }

        return $imports;
    }

    /**
     * @param File  $file
     * @param array $dependencies
     */
    private function toTree(File $file, array $dependencies): RootFile
    {
        $root  = new RootFile($file);
        $nodes = new \SplObjectStorage();

        $nodes[$file] = $root;

        foreach ($dependencies as $dependency) {
            $nodes[$dependency[0]] = new Dependency($dependency[0], $dependency[2], $dependency[3]);
        }

        foreach ($dependencies as $dependency) {
            $nodes[$dependency[1]]->addChild($nodes[$dependency[0]]);
        }

        return $root;
    }
}

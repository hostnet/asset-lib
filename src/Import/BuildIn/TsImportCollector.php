<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import\BuildIn;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\FileResolverInterface;
use Hostnet\Component\Resolver\Import\ImportCollection;
use Hostnet\Component\Resolver\Import\ImportCollectorInterface;

/**
 * Import resolver for JS files.
 */
final class TsImportCollector implements ImportCollectorInterface
{
    private $js_import_collector;
    private $nodejs_resolver;

    public function __construct(JsImportCollector $js_import_collector, FileResolverInterface $nodejs_resolver)
    {
        $this->js_import_collector = $js_import_collector;
        $this->nodejs_resolver = $nodejs_resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(File $file): bool
    {
        return $file->extension === 'ts';
    }

    /**
     * {@inheritdoc}
     */
    public function collect(string $cwd, File $file, ImportCollection $imports)
    {
        $content = file_get_contents($cwd . '/' . $file->path);
        $n = preg_match_all('/import(.*from)?\s+["\'](.*)["\'];/', $content, $matches);

        $this->js_import_collector->collect($cwd, $file, $imports);

        for ($i = 0; $i < $n; $i++) {
            $path = $matches[2][$i];

            try {
                $import = $this->nodejs_resolver->asRequire($path, $file);
                $base_name = basename($import->getImportedFile()->path);

                $ext = substr($base_name, strpos($base_name, '.'));

                if ($ext === '.d.ts') {
                    continue;
                }

                $imports->addImport($import);
            } catch (\RuntimeException $e) {
                continue;
            }
        }
    }
}

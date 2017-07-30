<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import\BuildIn;

use Hostnet\Component\Resolver\Import\ImportCollection;
use Hostnet\Component\Resolver\Import\ImportCollectorInterface;
use Hostnet\Component\Resolver\Import\ImportInterface;
use Hostnet\Component\Resolver\Import\Nodejs\FileResolver;

/**
 * Import resolver for JS files.
 */
class JsImportCollector implements ImportCollectorInterface
{
    private $nodejs_resolver;

    public function __construct(FileResolver $nodejs_resolver)
    {
        $this->nodejs_resolver = $nodejs_resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ImportInterface $file): bool
    {
        return $file->getExtension() === 'js';
    }

    /**
     * {@inheritdoc}
     */
    public function collect(string $cwd, ImportInterface $file, ImportCollection $imports)
    {
        $content = file_get_contents($cwd . '/' . $file->getPath());
        $n = preg_match_all('/(.?)require\(([\']([^\']+)[\']|["]([^"]+)["])\)/', $content, $matches);

        for ($i = 0; $i < $n; $i++) {
            $path = $matches[3][$i] ?: $matches[4][$i];

            // do we have a valid require?
            if (1 === preg_match('/[a-zA-Z_0-9.]/', $matches[1][$i])) {
                continue;
            }

            try {
                $imports->addImport($this->nodejs_resolver->asRequire($path, $file));
            } catch (\RuntimeException $e) {
                continue;
            }
        }
    }
}

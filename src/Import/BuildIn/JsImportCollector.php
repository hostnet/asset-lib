<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import\BuildIn;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\FileResolverInterface;
use Hostnet\Component\Resolver\Import\ImportCollection;
use Hostnet\Component\Resolver\Import\ImportCollectorInterface;
use Hostnet\Component\Resolver\Import\Nodejs\Exception\FileNotFoundException;

/**
 * Import resolver for JS files.
 */
final class JsImportCollector implements ImportCollectorInterface
{
    private $nodejs_resolver;

    public function __construct(FileResolverInterface $nodejs_resolver)
    {
        $this->nodejs_resolver = $nodejs_resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(File $file): bool
    {
        return $file->getExtension() === 'js';
    }

    /**
     * {@inheritdoc}
     */
    public function collect(string $cwd, File $file, ImportCollection $imports)
    {
        $content = file_get_contents($cwd . '/' . $file->getPath());
        $n = preg_match_all('/(.?)require\(([\']([^\']+)[\']|["]([^"]+)["])\)/', $content, $matches);

        for ($i = 0; $i < $n; $i++) {
            $path = $matches[3][$i] ?: $matches[4][$i];

            // do we have a valid require?
            if (1 === preg_match('/[a-zA-Z_0-9.]/', $matches[1][$i])) {
                continue;
            }

            $imports->addImport($this->nodejs_resolver->asRequire($path, $file));
        }
    }
}

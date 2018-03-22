<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import\BuiltIn;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\FileResolverInterface;
use Hostnet\Component\Resolver\Import\ImportCollection;
use Hostnet\Component\Resolver\Import\ImportCollectorInterface;

/**
 * Import resolver for JS files.
 */
final class JsImportCollector implements ImportCollectorInterface
{
    private $nodejs_resolver;

    private $extensions;

    public function __construct(FileResolverInterface $nodejs_resolver, array $extensions = ['js'])
    {
        $this->nodejs_resolver = $nodejs_resolver;
        $this->extensions      = $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(File $file): bool
    {
        return in_array($file->extension, $this->extensions, true);
    }

    /**
     * {@inheritdoc}
     */
    public function collect(string $cwd, File $file, ImportCollection $imports): void
    {
        $content = file_get_contents(File::makeAbsolutePath($file->path, $cwd));
        $n       = preg_match_all('/(.?)require\(([\']([^\']+)[\']|["]([^"]+)["])\)/', $content, $matches);

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

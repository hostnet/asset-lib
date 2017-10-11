<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import\BuiltIn;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\Import;
use Hostnet\Component\Resolver\Import\ImportCollection;
use Hostnet\Component\Resolver\Import\ImportCollectorInterface;

/**
 * Angular asset resolver.
 */
final class AngularImportCollector implements ImportCollectorInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(File $file): bool
    {
        return $file->extension === 'ts' && '.component.ts' === substr($file->path, -13);
    }

    /**
     * {@inheritdoc}
     */
    public function collect(string $cwd, File $file, ImportCollection $imports): void
    {
        $content = file_get_contents(File::makeAbsolutePath($file->path, $cwd));

        if (preg_match_all('/templateUrl\s*:(\s*[\'"`](.*?)[\'"`]\s*)/m', $content, $matches) > 0) {
            foreach ($matches[2] as $match) {
                $file_path = $match;

                if ($file_path[0] === '.') {
                    $file_path = $file->dir . substr($file_path, 1);
                }

                $imports->addImport(new Import($file_path, new File($file_path), true));
            }
        }
        if (preg_match_all('/styleUrls *:(\s*\[[^\]]*?\])/', $content, $matches) > 0) {
            foreach ($matches[1] as $match) {
                if (preg_match_all('/([\'`"])((?:[^\\\\]\\\\\1|.)*?)\1/', $match, $inner_matches) > 0) {
                    foreach ($inner_matches[2] as $inner_match) {
                        $file_path = $inner_match;

                        if ($file_path[0] === '.') {
                            $file_path = $file->dir . substr($file_path, 1);
                        }

                        $imports->addImport(new Import($file_path, new File($file_path), true));
                    }
                }
            }
        }
    }
}

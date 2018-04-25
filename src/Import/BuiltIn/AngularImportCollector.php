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
        return $file->extension === 'ts' && \strlen($file->path) - 13 === strrpos($file->path, '.component.ts');
    }

    /**
     * {@inheritdoc}
     */
    public function collect(string $cwd, File $file, ImportCollection $imports): void
    {
        $content = file_get_contents(File::makeAbsolutePath($file->path, $cwd));

        if (preg_match_all('/templateUrl\s*:(\s*[\'"`](.*?)[\'"`]\s*)/m', $content, $matches) > 0) {
            foreach ($matches[2] as $match) {
                $file_path = File::clean($file->dir . '/' . $match);

                $imports->addImport(new Import($file_path, new File($file_path), true));
            }
        }
        if (preg_match_all('/styleUrls\s*:(\s*\[[^\]]*?\])/', $content, $matches) <= 0) {
            return;
        }

        foreach ($matches[1] as $match) {
            if (preg_match_all('/([\'`"])((?:[^\\\\]\\\\\1|.)*?)\1/', $match, $inner_matches) <= 0) {
                continue;
            }

            foreach ($inner_matches[2] as $inner_match) {
                $file_path = File::clean($file->dir . '/' . $inner_match);

                $imports->addImport(new Import($file_path, new File($file_path), true));
            }
        }
    }
}

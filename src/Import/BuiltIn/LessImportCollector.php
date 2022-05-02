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
 * Import resolver for LESS files.
 */
final class LessImportCollector implements ImportCollectorInterface
{
    public function supports(File $file): bool
    {
        return $file->extension === 'less';
    }

    public function collect(string $cwd, File $file, ImportCollection $imports): void
    {
        $content = file_get_contents(File::makeAbsolutePath($file->path, $cwd));
        $n       = preg_match_all('/@import (\([a-z,\s]*\)\s*)?(url\()?(\'([^\']+)\'|"([^"]+)")/', $content, $matches);

        for ($i = 0; $i < $n; $i++) {
            $path = $matches[4][$i] ?: $matches[5][$i];

            // there can be :// which indicates a transport protocol, it (should) never be to a file.
            if (false !== strpos($path, '://')) {
                continue;
            }

            $import = new Import($path, new File(File::clean($file->dir . '/' . $path)), true);

            if (empty($import->getImportedFile()->extension)) {
                // all imports are virtual, since the less compiler will squash everything.
                $import = new Import($path, new File($import->getImportedFile()->path . '.less'), true);
            }

            $imports->addImport($import);
        }
    }
}

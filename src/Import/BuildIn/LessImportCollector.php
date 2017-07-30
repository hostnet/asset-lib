<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import\BuildIn;

use Hostnet\Component\Resolver\Import\File;
use Hostnet\Component\Resolver\Import\Import;
use Hostnet\Component\Resolver\Import\ImportCollection;
use Hostnet\Component\Resolver\Import\ImportCollectorInterface;
use Hostnet\Component\Resolver\Import\ImportInterface;

/**
 * Import resolver for LESS files.
 */
class LessImportCollector implements ImportCollectorInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(ImportInterface $file): bool
    {
        return $file->getExtension() === 'less';
    }

    /**
     * {@inheritdoc}
     */
    public function collect(string $cwd, ImportInterface $file, ImportCollection $imports)
    {
        $content = file_get_contents($cwd . '/' . $file->getPath());
        $n = preg_match_all('/@import (\([a-z,\s]*\)\s*)?(url\()?(\'([^\']+)\'|"([^"]+)")/', $content, $matches);

        for ($i = 0; $i < $n; $i++) {
            $path = $matches[4][$i] ?: $matches[5][$i];

            // there can be :// which indicates a transport protocol, it (should) never be to a file.
            if (false !== strpos($path, '://')) {
                continue;
            }

            $import = new Import($path, new File(File::clean($file->getDirectory() . '/' . $path)), true);

            if (empty($import->getImportedFile()->getExtension())) {
                // all imports are virtual, since the less compiler will squash everything.
                $import = new Import($path, new File($import->getImportedFile()->getPath() . '.less'), true);
            }

            $imports->addImport($import);
        }
    }
}

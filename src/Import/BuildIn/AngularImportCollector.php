<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import\BuildIn;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\ImportCollection;
use Hostnet\Component\Resolver\Import\ImportCollectorInterface;

/**
 * Angular asset resolver.
 */
class AngularImportCollector implements ImportCollectorInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(File $file): bool
    {
        return $file->getExtension() === 'ts' && 1 === preg_match('/\.component\.ts$/', $file->getPath());
    }

    /**
     * {@inheritdoc}
     */
    public function collect(string $cwd, File $file, ImportCollection $imports)
    {
        $content = file_get_contents($cwd . '/' . $file->getPath());

        if (preg_match_all('/templateUrl\s*:(\s*[\'"`](.*?)[\'"`]\s*)/m', $content, $matches) > 0) {
            foreach ($matches[2] as $match) {
                $file_path = $match;

                if ($file_path[0] === '.') {
                    $file_path = $file->getDirectory() . substr($file_path, 1);
                }

                $imports->addResource(new File($file_path));
            }
        }
        if (preg_match_all('/styleUrls *:(\s*\[[^\]]*?\])/', $content, $matches) > 0) {
            foreach ($matches[1] as $match) {
                if (preg_match_all('/([\'`"])((?:[^\\\\]\\\\\1|.)*?)\1/', $match, $inner_matches) > 0) {
                    foreach ($inner_matches[2] as $inner_match) {
                        $file_path = $inner_match;

                        if ($file_path[0] === '.') {
                            $file_path = $file->getDirectory() . substr($file_path, 1);
                        }

                        $imports->addResource(new File($file_path));
                    }
                }
            }
        }
    }
}

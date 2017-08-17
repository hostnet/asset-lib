<?php
namespace Hostnet\Component\Resolver\Transform\BuildIn;

use Hostnet\Component\Resolver\Import\ImportInterface;
use Hostnet\Component\Resolver\Transform\ContentTransformerInterface;
use Hostnet\Component\Resolver\Transform\TransformException;
use Symfony\Component\Process\Process;

class UglifyJsTransformer implements ContentTransformerInterface
{
    private $cache_dir;

    public function __construct(string $cache_dir)
    {
        $this->cache_dir = $cache_dir;
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
    public function transform(ImportInterface $file, string $content, string $cwd, string $output_dir): string
    {
        $tmp = $this->cache_dir . '/' . str_replace('.', '_', uniqid('uglifyjs', true));

        try {
            file_put_contents($tmp, $content);

            $process = new Process($cwd . '/vendor/bin/node ' . __DIR__ . '/js/uglify.js ' . $tmp, null, [
                'NODE_PATH' => $cwd . '/node_modules'
            ]);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new TransformException(
                    sprintf('Cannot transform "%s" due to uglifyjs error.', $file->getPath()),
                    $process->getErrorOutput()
                );
            }

            return $process->getOutput();
        } finally {
            unlink($tmp);
        }

    }
}

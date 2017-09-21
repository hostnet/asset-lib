<?php
namespace Hostnet\Component\Resolver\Transform\BuildIn;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Hostnet\Component\Resolver\Transform\ContentTransformerInterface;
use Hostnet\Component\Resolver\Transform\TransformException;
use Symfony\Component\Process\Process;

class CleanCssTransformer implements ContentTransformerInterface
{
    private $nodejs;
    private $cache_dir;

    public function __construct(Executable $nodejs, string $cache_dir)
    {
        $this->nodejs = $nodejs;
        $this->cache_dir = $cache_dir;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(File $file): bool
    {
        return $file->extension === 'css';
    }

    /**
     * {@inheritdoc}
     */
    public function transform(File $file, string $content, string $cwd, string $output_dir): string
    {
        $tmp = $this->cache_dir . '/' . str_replace('.', '_', uniqid('cleancss', true));

        if (!file_exists($this->cache_dir)){
            mkdir($this->cache_dir, 0777, true);
        }

        try {
            file_put_contents($tmp, $content);

            $process = new Process($this->nodejs->getBinary() . ' ' . __DIR__ . '/js/cleancss.js ' . $tmp, null, [
                'NODE_PATH' => $this->nodejs->getNodeModulesLocation()
            ]);
            $process->inheritEnvironmentVariables();
            $process->run();

            if (!$process->isSuccessful()) {
                throw new TransformException(
                    sprintf('Cannot transform "%s" due to clean-css error.', $file->path),
                    $process->getErrorOutput()
                );
            }

            return $process->getOutput();
        } finally {
            unlink($tmp);
        }
    }
}

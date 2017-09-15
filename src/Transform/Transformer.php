<?php

namespace Hostnet\Component\Resolver\Transform;

use Hostnet\Component\Resolver\File;

final class Transformer implements TransformerInterface
{
    public const POST_TRANSPILE = 'post_transpile';
    public const PRE_WRITE = 'pre_write';

    public const ALL = [
        self::PRE_WRITE,
        self::POST_TRANSPILE
    ];

    /**
     * @var ContentTransformerInterface[][]
     */
    private $transformers = [
        self::POST_TRANSPILE => [],
        self::PRE_WRITE => [],
    ];
    private $cwd;

    public function __construct(string $cwd)
    {
        $this->cwd = $cwd;
    }

    /**
     * Add a transformer for a given action.
     *
     * @param string                      $action
     * @param ContentTransformerInterface $transformer
     */
    public function addTransformer(string $action, ContentTransformerInterface $transformer)
    {
        $this->transformers[$action][] = $transformer;
    }

    /**
     * {@inheritdoc}
     */
    public function onPostTranspile(File $file, string $content, string $output_dir): string
    {
        return $this->on(self::POST_TRANSPILE, $file, $content, $output_dir);
    }

    /**
     * {@inheritdoc}
     */
    public function onPreWrite(File $file, string $content, string $output_dir): string
    {
        return $this->on(self::PRE_WRITE, $file, $content, $output_dir);
    }

    /**
     * Call an event to trigger to transform on.
     *
     * @param string $action
     * @param File   $file
     * @param string $content
     * @param string $output_dir
     * @return string
     */
    private function on(string $action, File $file, string $content, string $output_dir): string
    {
        foreach ($this->transformers[$action] as $transformer) {
            if ($transformer->supports($file)) {
                $content = $transformer->transform($file, $content, $this->cwd, $output_dir);
            }
        }

        return $content;
    }
}

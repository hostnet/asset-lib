<?php

namespace Hostnet\Component\Resolver\Transform;

use Hostnet\Component\Resolver\Import\ImportInterface;

class Transformer implements TransformerInterface
{
    /**
     * @var ContentTransformerInterface[]
     */
    private $transformers = [];

    /**
     * Add a collector the the finder.
     *
     * @param ContentTransformerInterface $transformer
     */
    public function addTransformer(ContentTransformerInterface $transformer)
    {
        $this->transformers[] = $transformer;
    }

    public function transform(ImportInterface $file, string $content, string $output_dir): string
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->supports($file)) {
                $content = $transformer->transform($file, $content, $output_dir);
            }
        }

        return $content;
    }
}

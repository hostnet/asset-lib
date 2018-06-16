<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

/**
 * Each writer will write the content of a module to disk. Having multiple writers means you can write the file in
 * different formats. For instance, you might want to output a gziped version of a file if your web-server does not
 * gzip this natively.
 *
 * Writers have an accepting extension. This can also be "*" in case the writer can accept all extensions.
 * Additionally, a writer is not required to always output a file. (The generic writer will always output the file
 * as-is)
 */
abstract class AbstractWriter
{
    /**
     * Return the extension this writer accepts. This should include the dot. I.e., ".css" or ".js". If the writer
     * accepts all extensions (or does it's own logic) it can also return "*".
     *
     * @return string
     */
    abstract public function acceptedExtension(): string;

    /**
     * Return the javascript module this writer uses.
     *
     * @return string
     */
    abstract public function getJsModule(): string;

    /**
     * Calculate a unique hash for this writer.
     *
     * @return string
     */
    final public function getHash(): string
    {
        return serialize([
            \get_class($this),
            $this->acceptedExtension(),
            $this->getJsModule(),
        ]);
    }
}

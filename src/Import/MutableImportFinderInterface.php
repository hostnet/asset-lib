<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import;

/**
 * Implementation of this interface allow for finding of imports.
 */
interface MutableImportFinderInterface extends ImportFinderInterface
{
    /**
     * Add a collector the the finder.
     *
     * @param ImportCollectorInterface $import_collector
     */
    public function addCollector(ImportCollectorInterface $import_collector): void;
}

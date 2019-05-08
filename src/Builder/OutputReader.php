<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Report\ReporterInterface;

/**
 * Helper class for buffering the build.js output and reporting it.
 */
class OutputReader
{
    private $reporter;
    private $buffer = '';

    public function __construct(ReporterInterface $reporter)
    {
        $this->reporter = $reporter;
    }

    public function append(string $content): void
    {
        $this->buffer .= $content;

        // Now parse it line-by-line
        while (false !== ($i = strpos($this->buffer, "\n"))) {
            $line         = substr($this->buffer, 0, $i);
            $this->buffer = substr($this->buffer, $i + 1);

            $this->parseAndReport($line);
        }
    }

    private function parseAndReport(string $line): void
    {
        ['action' => $action, 'file' => $file, 'metadata' => $metadata] = json_decode($line, true);

        switch ($action) {
            case 'WRITE':
                $this->reporter->reportFileState(new File($file), ReporterInterface::STATE_BUILT);
                $this->reporter->reportOutputFile(new File($file));
                break;
            case 'FILE_INIT':
                $this->reporter->reportFileState(new File($file), ReporterInterface::STATE_BUILT);
                break;
            case 'BUILD_ADDITIONAL':
                $this->reporter->reportChildOutputFile(new File($file), new File($metadata['parent']));
                break;
            case 'FILE_CACHE':
                $this->reporter->reportFileState(new File($file), ReporterInterface::STATE_FROM_CACHE);
                break;
        }
    }
}

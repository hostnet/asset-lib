<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Report;

use Hostnet\Component\Resolver\File;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Report\NullReporter
 */
class NullReporterTest extends TestCase
{
    public function testGeneric()
    {
        $null_reporter = new NullReporter();

        $null_reporter->reportFileDependencies(new File(__FILE__), []);
        $null_reporter->reportFileContent(new File(__FILE__), '');
        $null_reporter->reportFileState(new File(__FILE__), ReporterInterface::STATE_BUILT);
        $null_reporter->reportOutputFile(new File(__FILE__));

        $this->addToAssertionCount(1);
    }
}

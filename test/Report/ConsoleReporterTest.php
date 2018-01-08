<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Report;

use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\Dependency;
use Hostnet\Component\Resolver\Import\RootFile;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @covers \Hostnet\Component\Resolver\Report\ConsoleReporter
 */
class ConsoleReporterTest extends TestCase
{
    private $config;

    /**
     * @var ConsoleReporter
     */
    private $console_reporter;

    protected function setUp()
    {
        $this->config = $this->prophesize(ConfigInterface::class);

        $this->console_reporter = new ConsoleReporter($this->config->reveal(), true);
    }

    public function testPrintReportEmpty()
    {
        $output = new BufferedOutput();
        $this->console_reporter->printReport($output);

        self::assertStringEqualsFile(__DIR__ . '/report.empty.txt', $output->fetch());
    }

    public function testPrintReport()
    {
        $this->config->getProjectRoot()->willReturn(__DIR__);

        $output = new BufferedOutput();
        $file1  = new File('fixtures/a.js');
        $file2  = new File('fixtures/b.js');
        $file3  = new File('fixtures/c.js');
        $file4  = new File('fixtures/d.js');

        $dep1 = new Dependency($file3);
        $dep2 = new Dependency($file4);
        $root = new RootFile($file1);
        $root->addChild($dep1);
        $dep1->addChild($dep2);

        $this->console_reporter->reportOutputFile($file1);
        $this->console_reporter->reportOutputFile($file2);
        $this->console_reporter->reportFileState($file1, ReporterInterface::STATE_BUILD);
        $this->console_reporter->reportFileState($file2, ReporterInterface::STATE_UP_TO_DATE);
        $this->console_reporter->reportFileState($file3, ReporterInterface::STATE_BUILD);
        $this->console_reporter->reportFileState($file4, ReporterInterface::STATE_BUILD);
        $this->console_reporter->reportFileSize($file1, 1337);
        $this->console_reporter->reportFileDependencies($file1, [$root, $dep1, $dep2]);
        $this->console_reporter->printReport($output);

        self::assertStringEqualsFile(__DIR__ . '/report.txt', $output->fetch());
    }
}

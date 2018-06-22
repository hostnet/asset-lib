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
use Hostnet\Component\Resolver\Report\Helper\FileSizeHelperInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @covers \Hostnet\Component\Resolver\Report\ConsoleReporter
 */
class ConsoleReporterTest extends TestCase
{
    private $config;
    private $helper;

    /**
     * @var ConsoleReporter
     */
    private $console_reporter;

    protected function setUp()
    {
        $this->config = $this->prophesize(ConfigInterface::class);
        $this->helper = $this->prophesize(FileSizeHelperInterface::class);

        $this->console_reporter = new ConsoleReporter($this->config->reveal(), $this->helper->reveal(), true);
    }

    public function testPrintReportEmpty()
    {
        $output = new BufferedOutput();
        $this->console_reporter->printReport($output);

        self::assertStringEqualsFile(__DIR__ . '/report.empty.txt', str_replace("\r\n", "\n", $output->fetch()));
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

        $this->helper->filesize(__DIR__ . '/fixtures/a.js')->willReturn(3);
        $this->helper->filesize(__DIR__ . '/fixtures/b.js')->willReturn(300);
        $this->helper->filesize(__DIR__ . '/fixtures/c.js')->willReturn(12);
        $this->helper->filesize(__DIR__ . '/fixtures/d.js')->willReturn(42);
        $this->helper->format(3)->willReturn('3 b');
        $this->helper->format(300)->willReturn('300 b');
        $this->helper->format(12)->willReturn('12 b');
        $this->helper->format(42)->willReturn('42 b');
        $this->helper->format(1337)->willReturn('1 kb');

        $this->console_reporter->reportOutputFile($file1);
        $this->console_reporter->reportOutputFile($file2);
        $this->console_reporter->reportFileState($file1, ReporterInterface::STATE_BUILT);
        $this->console_reporter->reportFileState($file2, ReporterInterface::STATE_UP_TO_DATE);
        $this->console_reporter->reportFileState($file3, ReporterInterface::STATE_BUILT);
        $this->console_reporter->reportFileState($file4, ReporterInterface::STATE_BUILT);
        $this->console_reporter->reportFileContent($file1, str_repeat('a', 1337));
        $this->console_reporter->reportFileDependencies($file1, [$root, $dep1, $dep2]);
        $this->console_reporter->printReport($output);

        self::assertStringEqualsFile(__DIR__ . '/report.txt', str_replace("\r\n", "\n", $output->fetch()));
    }
}

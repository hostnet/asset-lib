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
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \Hostnet\Component\Resolver\Report\ConsoleLoggingReporter
 */
class ConsoleLoggingReporterTest extends TestCase
{
    public function testGeneric()
    {
        $config         = $this->prophesize(ConfigInterface::class);
        $console_output = new BufferedOutput();

        $config->getProjectRoot()->willReturn(__DIR__);

        $console_logging_reporter = new ConsoleLoggingReporter($config->reveal(), $console_output);

        $file1  = new File('fixtures/a.js');
        $file2  = new File('fixtures/b.js');
        $file3  = new File('fixtures/c.js');
        $file4  = new File('fixtures/d.js');

        $dep1 = new Dependency($file3);
        $dep2 = new Dependency($file4);
        $root = new RootFile($file1);
        $root->addChild($dep1);
        $dep1->addChild($dep2);

        $console_logging_reporter->reportOutputFile($file1);
        $console_logging_reporter->reportOutputFile($file2);
        $console_logging_reporter->reportFileState($file1, ReporterInterface::STATE_BUILD);
        $console_logging_reporter->reportFileState($file2, ReporterInterface::STATE_UP_TO_DATE);
        $console_logging_reporter->reportFileState($file3, ReporterInterface::STATE_BUILD);
        $console_logging_reporter->reportFileState($file4, ReporterInterface::STATE_BUILD);
        $console_logging_reporter->reportFileSize($file1, 1337);
        $console_logging_reporter->reportFileDependencies($file1, [$root, $dep1, $dep2]);

        self::assertStringEqualsFile(__DIR__ . '/log.txt', str_replace("\r\n", "\n", $console_output->fetch()));
    }
}

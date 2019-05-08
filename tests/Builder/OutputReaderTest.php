<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Report\ReporterInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Builder\OutputReader
 */
class OutputReaderTest extends TestCase
{
    private $reporter;

    /**
     * @var OutputReader
     */
    private $output_reader;

    protected function setUp(): void
    {
        $this->reporter = $this->prophesize(ReporterInterface::class);

        $this->output_reader = new OutputReader(
            $this->reporter->reveal()
        );
    }

    public function testReading(): void
    {
        $this->reporter->reportFileState(new File('a.js'), ReporterInterface::STATE_BUILT)->shouldBeCalled();
        $this->reporter->reportFileState(new File('b.js'), ReporterInterface::STATE_BUILT)->shouldBeCalled();
        $this->reporter->reportFileState(new File('c.js'), ReporterInterface::STATE_BUILT)->shouldBeCalled();
        $this->reporter->reportFileState(new File('d.js'), ReporterInterface::STATE_FROM_CACHE)->shouldBeCalled();
        $this->reporter->reportOutputFile(new File('a.js'))->shouldBeCalled();
        $this->reporter->reportChildOutputFile(new File('c.js'), new File('b.js'))->shouldBeCalled();

        // Single
        $this->output_reader->append(json_encode(['action' => 'WRITE', 'file' => 'a.js', 'metadata' => []]) . "\n");
        // Multiple
        $this->output_reader->append(
            json_encode(['action' => 'FILE_INIT', 'file' => 'b.js', 'metadata' => []]) . "\n" .
            json_encode(['action' => 'FILE_INIT', 'file' => 'c.js', 'metadata' => []]) . "\n" .
            json_encode(['action' => 'BUILD_ADDITIONAL', 'file' => 'c.js', 'metadata' => ['parent' => 'b.js']]) . "\n"
        );
        // In parts
        $line = json_encode(['action' => 'FILE_CACHE', 'file' => 'd.js', 'metadata' => []]) . "\n";

        $this->output_reader->append(substr($line, 0, 10));
        $this->output_reader->append(substr($line, 10));
    }
}

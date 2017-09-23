<?php
namespace Hostnet\Component\Resolver\Functional;

use Hostnet\Component\Resolver\Packer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Debug\BufferingLogger;

class BundlerTest extends TestCase
{
    public function testPipeline()
    {
        $fixtures_folder = __DIR__ . '/../fixtures';
        $logger = new BufferingLogger();

        Packer::pack($fixtures_folder, $logger, false);

        var_dump($logger->cleanLogs());
    }
}

<?php
namespace Hostnet\Component\Resolver\Functional;

use Hostnet\Component\Resolver\Packer;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class BundlerTest extends TestCase
{
    public function testBundler()
    {
        $fixtures_folder = __DIR__ . '/../fixtures';

        `rm -rf $fixtures_folder/web/*`;

//        Packer::pack($fixtures_folder, new NullLogger());
        Packer::pack($fixtures_folder, new NullLogger(), true);
    }
}

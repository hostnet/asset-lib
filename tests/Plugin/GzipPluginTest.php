<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Builder\Writer\GzipFileWriter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @covers \Hostnet\Component\Resolver\Plugin\GzipPlugin
 */
class GzipPluginTest extends TestCase
{
    use ProphecyTrait;

    public function testActivate(): void
    {
        $gzip_plugin = new GzipPlugin();

        $plugin_api = $this->prophesize(PluginApi::class);
        $plugin_api->addWriter(Argument::type(GzipFileWriter::class))->shouldBeCalled();

        $gzip_plugin->activate($plugin_api->reveal());
    }
}

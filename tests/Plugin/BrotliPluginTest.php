<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Builder\Writer\BrotliFileWriter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @covers \Hostnet\Component\Resolver\Plugin\BrotliPlugin
 */
class BrotliPluginTest extends TestCase
{
    public function testActivate(): void
    {
        $brotli_plugin = new BrotliPlugin();

        $plugin_api = $this->prophesize(PluginApi::class);
        $plugin_api->addWriter(Argument::type(BrotliFileWriter::class))->shouldBeCalled();

        $brotli_plugin->activate($plugin_api->reveal());
    }
}

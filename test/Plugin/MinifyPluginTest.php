<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Builder\Step\CleanCssBuildStep;
use Hostnet\Component\Resolver\Builder\Step\UglifyBuildStep;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @covers \Hostnet\Component\Resolver\Plugin\MinifyPlugin
 */
class MinifyPluginTest extends TestCase
{
    public function testActivate()
    {
        $minify_plugin = new MinifyPlugin();

        $plugin_api = $this->prophesize(PluginApi::class);
        $plugin_api->addBuildStep(Argument::type(UglifyBuildStep::class))->shouldBeCalled();
        $plugin_api->addBuildStep(Argument::type(CleanCssBuildStep::class))->shouldBeCalled();

        $minify_plugin->activate($plugin_api->reveal());
    }
}

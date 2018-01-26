<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\Event\FileEvent;
use Hostnet\Component\Resolver\Event\FileEvents;
use Hostnet\Component\Resolver\File;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @covers \Hostnet\Component\Resolver\Plugin\GzipPlugin
 */
class GzipPluginTest extends TestCase
{
    public function testActivate()
    {
        $plugin = new GzipPlugin();

        $dispatcher = new EventDispatcher();

        $config = $this->prophesize(ConfigInterface::class);
        $config->getEventDispatcher()->willReturn($dispatcher);
        $config->getProjectRoot()->willReturn('');

        $api = $this->prophesize(PluginApi::class);
        $api->getConfig()->willReturn($config->reveal());

        $plugin->activate($api->reveal());

        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid();

        try {
            $contents = str_repeat('file contents', 5);

            $event = new FileEvent(new File($file), $contents);

            $dispatcher->dispatch(FileEvents::POST_WRITE, $event);

            self::assertTrue(file_exists($file . '.gz'));
            self::assertEquals($contents, gzdecode(file_get_contents($file . '.gz')));
        } finally {
            @unlink($file);
            @unlink($file . '.gz');
        }
    }

    public function testActivateNoGzipImprovement()
    {
        $plugin = new GzipPlugin();

        $dispatcher = new EventDispatcher();

        $config = $this->prophesize(ConfigInterface::class);
        $config->getEventDispatcher()->willReturn($dispatcher);

        $api = $this->prophesize(PluginApi::class);
        $api->getConfig()->willReturn($config->reveal());

        $plugin->activate($api->reveal());

        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid();

        try {
            $event = new FileEvent(new File($file), '');

            $dispatcher->dispatch(FileEvents::POST_WRITE, $event);

            self::assertFalse(file_exists($file . '.gz'));
        } finally {
            @unlink($file);
            @unlink($file . '.gz');
        }
    }
}

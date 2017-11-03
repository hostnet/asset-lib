<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Config;

use Hostnet\Component\Resolver\Bundler\Runner\SingleProcessRunner;
use Hostnet\Component\Resolver\Bundler\Runner\UnixSocketRunner;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Plugin\PluginInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @covers \Hostnet\Component\Resolver\Config\FileConfig
 */
class FileConfigTest extends TestCase
{
    private const EXPECTED_OUTPUT = 'web' . DIRECTORY_SEPARATOR . 'dev';

    public function testMinimal()
    {
        $config = new FileConfig(__DIR__.'/../fixtures/configs/minimal.config.json', [], true);

        self::assertTrue($config->isDev());
        self::assertSame(__DIR__ . '/../fixtures/configs', $config->getProjectRoot());
        self::assertSame([], $config->getIncludePaths());
        self::assertSame([], $config->getEntryPoints());
        self::assertSame([], $config->getAssetFiles());
        self::assertSame(self::EXPECTED_OUTPUT, $config->getOutputFolder());
        self::assertSame('dev', $config->getOutputFolder(false));
        self::assertSame('', $config->getSourceRoot());
        self::assertSame(__DIR__ . '/../fixtures/configs/var', $config->getCacheDir());
        self::assertSame('/usr/bin/node', $config->getNodeJsExecutable()->getBinary());
        self::assertSame('/home/me/node_modules', $config->getNodeJsExecutable()->getNodeModulesLocation());
        self::assertInstanceOf(NullLogger::class, $config->getLogger());
        self::assertSame([], $config->getPlugins());
        self::assertInstanceOf(SingleProcessRunner::class, $config->getRunner());
    }

    public function testIncludePaths()
    {
        $dispatcher = new EventDispatcher();
        $logger     = new NullLogger();
        $plugins    = [$this->prophesize(PluginInterface::class)->reveal()];
        $config     = new FileConfig(
            __DIR__.'/../fixtures/configs/include-paths.config.json',
            $plugins,
            true,
            $dispatcher,
            $logger
        );

        self::assertTrue($config->isDev());
        self::assertSame(__DIR__.'/../fixtures/configs', $config->getProjectRoot());
        self::assertSame(['some_other_dir'], $config->getIncludePaths());
        self::assertSame([], $config->getEntryPoints());
        self::assertSame([], $config->getAssetFiles());
        self::assertSame(self::EXPECTED_OUTPUT, $config->getOutputFolder());
        self::assertSame('dev', $config->getOutputFolder(false));
        self::assertSame('', $config->getSourceRoot());
        self::assertSame(__DIR__ . '/../fixtures/configs/var', $config->getCacheDir());
        self::assertSame('/usr/bin/node', $config->getNodeJsExecutable()->getBinary());
        self::assertSame('/home/me/node_modules', $config->getNodeJsExecutable()->getNodeModulesLocation());
        self::assertSame($plugins, $config->getPlugins());
        self::assertSame($logger, $config->getLogger());
        self::assertSame($dispatcher, $config->getEventDispatcher());
        self::assertInstanceOf(UnixSocketRunner::class, $config->getRunner());
    }

    public function testMinimalRelativeNodePaths()
    {
        $config = new FileConfig(__DIR__.'/../fixtures/configs/minimal-relative.config.json', [], true);

        self::assertTrue($config->isDev());
        self::assertSame(__DIR__.'/../fixtures/configs', $config->getProjectRoot());
        self::assertSame([], $config->getIncludePaths());
        self::assertSame([], $config->getEntryPoints());
        self::assertSame([], $config->getAssetFiles());
        self::assertSame(self::EXPECTED_OUTPUT, $config->getOutputFolder());
        self::assertSame('dev', $config->getOutputFolder(false));
        self::assertSame('', $config->getSourceRoot());
        self::assertSame(__DIR__.'/../fixtures/configs/var', $config->getCacheDir());
        self::assertSame(
            File::clean(__DIR__.'/../fixtures/configs/bin/node'),
            $config->getNodeJsExecutable()->getBinary()
        );
        self::assertSame(
            File::clean(__DIR__.'/../fixtures/configs/node_modules'),
            $config->getNodeJsExecutable()->getNodeModulesLocation()
        );
        self::assertSame([], $config->getPlugins());
    }
}

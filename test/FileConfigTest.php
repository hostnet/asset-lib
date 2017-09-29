<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\FileConfig
 */
class FileConfigTest extends TestCase
{
    public function testMinimal()
    {
        $config = new FileConfig(true, __DIR__ . '/fixtures/configs/minimal.config.json');

        self::assertTrue($config->isDev());
        self::assertSame(__DIR__ . '/fixtures/configs', $config->cwd());
        self::assertSame([], $config->getEntryPoints());
        self::assertSame([], $config->getAssetFiles());
        self::assertSame('dev', $config->getOutputFolder());
        self::assertSame('web', $config->getWebRoot());
        self::assertSame('', $config->getSourceRoot());
        self::assertSame(__DIR__ . '/fixtures/configs/var', $config->getCacheDir());
        self::assertSame('/usr/bin/node', $config->getNodeJsBinary());
        self::assertSame('/home/me/node_modules', $config->getNodeModulesPath());
        self::assertFalse($config->isLessEnabled());
        self::assertFalse($config->isTsEnabled());
        self::assertFalse($config->isAngularEnabled());
    }
    public function testMinimalRelativeNodePaths()
    {
        $config = new FileConfig(true, __DIR__ . '/fixtures/configs/minimal-relative.config.json');

        self::assertTrue($config->isDev());
        self::assertSame(__DIR__ . '/fixtures/configs', $config->cwd());
        self::assertSame([], $config->getEntryPoints());
        self::assertSame([], $config->getAssetFiles());
        self::assertSame('dev', $config->getOutputFolder());
        self::assertSame('web', $config->getWebRoot());
        self::assertSame('', $config->getSourceRoot());
        self::assertSame(__DIR__ . '/fixtures/configs/var', $config->getCacheDir());
        self::assertSame(__DIR__ . '/fixtures/configs/bin/node', $config->getNodeJsBinary());
        self::assertSame(__DIR__ . '/fixtures/configs/node_modules', $config->getNodeModulesPath());
        self::assertFalse($config->isLessEnabled());
        self::assertFalse($config->isTsEnabled());
        self::assertFalse($config->isAngularEnabled());
    }
}

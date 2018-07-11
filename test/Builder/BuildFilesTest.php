<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\Dependency;
use Hostnet\Component\Resolver\Import\ImportFinderInterface;
use Hostnet\Component\Resolver\Import\RootFile;
use Hostnet\Component\Resolver\Report\NullReporter;
use Hostnet\Component\Resolver\Split\EntryPointSplittingStrategyInterface;
use Hostnet\Component\Resolver\Split\OneOnOneSplittingStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \Hostnet\Component\Resolver\Builder\BuildFiles
 */
class BuildFilesTest extends TestCase
{
    private $finder;
    private $extension_map;
    private $config;

    /**
     * @var BuildFiles
     */
    private $build_files;

    protected function setUp()
    {
        $this->finder        = $this->prophesize(ImportFinderInterface::class);
        $this->extension_map = new ExtensionMap(['.js' => '.js']);
        $this->config        = $this->prophesize(ConfigInterface::class);

        $this->build_files = new BuildFiles(
            $this->finder->reveal(),
            $this->extension_map,
            $this->config->reveal()
        );
    }

    public function testCompile(): void
    {
        $fs = new Filesystem();

        try {
            $fs->dumpFile(__DIR__ . '/dist/require.js', 'foo');
            $fs->dumpFile(__DIR__ . '/dist/fixtures/foo.js', 'foo');

            $this->config->getOutputFolder()->willReturn('dist');
            $this->config->getCacheDir()->willReturn(__DIR__ . '/var');
            $this->config->getSourceRoot()->willReturn('fixtures');
            $this->config->getProjectRoot()->willReturn(__DIR__);
            $this->config->isDev()->willReturn(true);
            $this->config->getReporter()->willReturn(new NullReporter());
            $this->config->getEntryPoints()->willReturn([
                'foo.js',
            ]);
            $this->config->getAssetFiles()->willReturn([
                'sub/bar.js',
            ]);
            $this->config->getSplitStrategy()->willReturn(new OneOnOneSplittingStrategy());

            $r1 = new RootFile(new File('fixtures/sub/bar.js'));
            $r1->addChild(new Dependency(new File('fixtures/fez.js'), true));

            $this->finder->all(new File('fixtures/foo.js'))->willReturn(new RootFile(new File('fixtures/foo.js')));
            $this->finder->all(new File('fixtures/sub/bar.js'))->willReturn($r1);

            $this->build_files->compile(true);

            $data = $this->build_files->jsonSerialize();

            self::assertTrue($this->build_files->hasFiles());
            self::assertSame([
                'dist/require.js' => [
                    [
                        '../../src/Builder/js/require.js',
                        '.js',
                        '../../src/Builder/js/require.js',
                        true,
                        true,
                        '../../src/Builder/js',
                    ],
                ],
                'dist/fixtures/foo.js' => [
                    [
                        'fixtures/foo.js',
                        '.js',
                        'foo.js',
                        true,
                        false,
                        '',
                    ],
                ],
                'dist/sub/bar.js' => [
                    [
                        'fixtures/sub/bar.js',
                        '.js',
                        'sub/bar.js',
                        true,
                        false,
                        'sub',
                    ],
                ],
            ], $data['input']);
        } finally {
            // clean the var folder
            $fs->remove([__DIR__ . '/dist']);
            $fs->remove([__DIR__ . '/var']);
        }
    }

    public function testCompileWithCacheAndOutput(): void
    {
        $fs = new Filesystem();

        try {
            $fs->dumpFile(__DIR__ . '/out/require.js', 'foo');
            $fs->dumpFile(__DIR__ . '/out/fixtures/foo.js', 'foo');

            $this->config->getOutputFolder()->willReturn('out');
            $this->config->getCacheDir()->willReturn(__DIR__ . '/var-saved');
            $this->config->getSourceRoot()->willReturn('fixtures');
            $this->config->getProjectRoot()->willReturn(__DIR__);
            $this->config->isDev()->willReturn(true);
            $this->config->getReporter()->willReturn(new NullReporter());
            $this->config->getEntryPoints()->willReturn([
                'foo.js',
            ]);
            $this->config->getAssetFiles()->willReturn([]);
            $this->config->getSplitStrategy()->willReturn(new OneOnOneSplittingStrategy());

            $this->finder->all(new File('fixtures/foo.js'))->willReturn(new RootFile(new File('fixtures/foo.js')));

            $this->build_files->compile();

            $data = $this->build_files->jsonSerialize();

            self::assertFalse($this->build_files->hasFiles());
            self::assertSame([], $data['input']);
        } finally {
            $fs->remove([__DIR__ . '/out']);
        }
    }

    public function testCompileWithOutdatedCacheAndOutput(): void
    {
        $fs = new Filesystem();

        try {
            $fs->dumpFile(__DIR__ . '/out/require.js', 'foo');
            $fs->dumpFile(__DIR__ . '/out/fixtures/foo.js', 'foo');
            $fs->mirror(__DIR__ . '/var-saved', __DIR__ . '/var-outdated');

            $this->config->getOutputFolder()->willReturn('out');
            $this->config->getCacheDir()->willReturn(__DIR__ . '/var-outdated');
            $this->config->getSourceRoot()->willReturn('fixtures');
            $this->config->getProjectRoot()->willReturn(__DIR__);
            $this->config->isDev()->willReturn(true);
            $this->config->getReporter()->willReturn(new NullReporter());
            $this->config->getEntryPoints()->willReturn([
                'foo.js',
            ]);
            $this->config->getAssetFiles()->willReturn([]);
            $this->config->getSplitStrategy()->willReturn(new OneOnOneSplittingStrategy());

            $root = new RootFile(new File('fixtures/foo.js'));
            $root->addChild(new Dependency(new File('fixtures/sub/bar.js')));
            $root->addChild(new Dependency(new File('fixtures/fez.js'), true));

            $this->finder->all(new File('fixtures/foo.js'))->willReturn($root);

            $this->build_files->compile();

            $data = $this->build_files->jsonSerialize();

            self::assertTrue($this->build_files->hasFiles());
            self::assertSame([
                'out/fixtures/foo.js' => [
                    [
                        'fixtures/foo.js',
                        '.js',
                        'foo.js',
                        false,
                        false,
                        '',
                    ],
                    [
                        'fixtures/sub/bar.js',
                        '.js',
                        'sub/bar.js',
                        false,
                        false,
                        'sub',
                    ],
                ],
            ], $data['input']);
        } finally {
            $fs->remove([__DIR__ . '/out']);
            $fs->remove([__DIR__ . '/var-outdated']);
        }
    }

    public function testHasFilesNotYetCompiled(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot count files if not yet compiled.');

        $this->build_files->hasFiles();
    }

    public function testCompileAlreadyCompiled(): void
    {
        try {
            $this->config->getOutputFolder()->willReturn('dist');
            $this->config->getCacheDir()->willReturn(__DIR__ . '/var');
            $this->config->getSourceRoot()->willReturn('');
            $this->config->getProjectRoot()->willReturn(__DIR__);
            $this->config->isDev()->willReturn(true);
            $this->config->getReporter()->willReturn(new NullReporter());
            $this->config->getEntryPoints()->willReturn([]);
            $this->config->getAssetFiles()->willReturn([]);

            $this->build_files->compile(true);

            $this->expectException(\LogicException::class);
            $this->expectExceptionMessage('Cannot recompile already compiled build files.');
            $this->build_files->compile();
        } finally {
            // clean the var folder
            $fs = new Filesystem();
            $fs->remove([__DIR__ . '/var']);
        }
    }

    public function testCompileEmptyEntryPoint(): void
    {
        try {
            $splitter = $this->prophesize(EntryPointSplittingStrategyInterface::class);

            $this->config->getOutputFolder()->willReturn('dist');
            $this->config->getCacheDir()->willReturn(__DIR__ . '/var');
            $this->config->getSourceRoot()->willReturn('fixtures');
            $this->config->getProjectRoot()->willReturn(__DIR__);
            $this->config->isDev()->willReturn(true);
            $this->config->getReporter()->willReturn(new NullReporter());
            $this->config->getEntryPoints()->willReturn([
                'foo.js',
            ]);
            $this->config->getAssetFiles()->willReturn([]);
            $this->config->getSplitStrategy()->willReturn($splitter);

            $this->finder->all(new File('fixtures/foo.js'))->willReturn(new RootFile(new File('fixtures/foo.js')));

            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Entry point "foo.js" did not resolve in any output file.');
            $this->build_files->compile();
        } finally {
            // clean the var folder
            $fs = new Filesystem();
            $fs->remove([__DIR__ . '/var']);
        }
    }
}

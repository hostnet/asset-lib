<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

use Hostnet\Component\Resolver\Builder\Step\IdentityBuildStep;
use Hostnet\Component\Resolver\Builder\Writer\GenericFileWriter;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\ImportFinderInterface;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Hostnet\Component\Resolver\Import\RootFile;
use Hostnet\Component\Resolver\Report\ReporterInterface;
use Hostnet\Component\Resolver\Split\OneOnOneSplittingStrategy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \Hostnet\Component\Resolver\Builder\Bundler
 */
class BundlerTest extends TestCase
{
    use ProphecyTrait;

    private $finder;
    private $config;

    /**
     * @var Bundler
     */
    private $bundler;

    protected function setUp(): void
    {
        $this->finder = $this->prophesize(ImportFinderInterface::class);
        $this->config = $this->prophesize(ConfigInterface::class);

        $this->bundler = new Bundler(
            $this->finder->reveal(),
            $this->config->reveal(),
            __DIR__ . '/mock_builder.php'
        );
    }

    public function testBundle(): void
    {
        try {
            // Fake node by using PHP and making the build script a php file.
            $node     = new Executable('php', '');
            $reporter = $this->prophesize(ReporterInterface::class);

            $this->config->getOutputFolder()->willReturn('dist');
            $this->config->getOutputFolder(true)->willReturn('dist');
            $this->config->getCacheDir()->willReturn(__DIR__ . '/var');
            $this->config->getSourceRoot()->willReturn('fixtures');
            $this->config->getProjectRoot()->willReturn(__DIR__);
            $this->config->isDev()->willReturn(true);
            $this->config->getReporter()->willReturn($reporter);
            $this->config->getEntryPoints()->willReturn([
                'foo.js',
            ]);
            $this->config->getAssetFiles()->willReturn([
                'bar.js',
            ]);
            $this->config->getSplitStrategy()->willReturn(new OneOnOneSplittingStrategy());
            $this->config->getNodeJsExecutable()->willReturn($node);

            $this->finder->all(new File('fixtures/foo.js'))->willReturn(new RootFile(new File('fixtures/foo.js')));
            $this->finder->all(new File('fixtures/bar.js'))->willReturn(new RootFile(new File('fixtures/bar.js')));

            $build_config = new BuildConfig($this->config->reveal());
            $build_config->registerStep(new IdentityBuildStep('.js'));
            $build_config->registerWriter(new GenericFileWriter());

            $reporter->reportFileDependencies(Argument::any(), Argument::any())->shouldBeCalled();
            $reporter->reportFileState(new File('a.js'), ReporterInterface::STATE_BUILT)->shouldBeCalled();
            $reporter->reportOutputFile(new File('a.js'))->shouldBeCalled();

            $this->bundler->bundle($build_config);

            // check the output
            self::assertJsonFileEqualsJsonFile(__DIR__ . '/stdin.expected.json', __DIR__ . '/out/stdin.json');
            self::assertSame([
                '--debug',
                '--log-json',
                '--stdin',
                __DIR__ . '/var/build_config.json',
            ], json_decode(file_get_contents(__DIR__ . '/out/args.json'), true));
        } finally {
            $fs = new Filesystem();
            $fs->remove([__DIR__ . '/var', __DIR__ . '/out']);
        }
    }

    public function testBundleWithCache(): void
    {
        try {
            // Fake node by using PHP and making the build script a php file.
            $node     = new Executable('php', '');
            $reporter = $this->prophesize(ReporterInterface::class);

            $this->config->getOutputFolder()->willReturn('dist');
            $this->config->getOutputFolder(true)->willReturn('dist');
            $this->config->getCacheDir()->willReturn(__DIR__ . '/var-saved');
            $this->config->getSourceRoot()->willReturn('fixtures');
            $this->config->getProjectRoot()->willReturn(__DIR__);
            $this->config->isDev()->willReturn(true);
            $this->config->getReporter()->willReturn($reporter);
            $this->config->getEntryPoints()->willReturn([
                'foo.js',
            ]);
            $this->config->getAssetFiles()->willReturn([
                'bar.js',
            ]);
            $this->config->getSplitStrategy()->willReturn(new OneOnOneSplittingStrategy());
            $this->config->getNodeJsExecutable()->willReturn($node);

            $this->finder->all(new File('fixtures/foo.js'))->willReturn(new RootFile(new File('fixtures/foo.js')));
            $this->finder->all(new File('fixtures/bar.js'))->willReturn(new RootFile(new File('fixtures/bar.js')));

            $build_config = new BuildConfig($this->config->reveal());
            $build_config->registerStep(new IdentityBuildStep('.js'));
            $build_config->registerWriter(new GenericFileWriter());

            $reporter->reportFileDependencies(Argument::any(), Argument::any())->shouldBeCalled();
            $reporter->reportFileState(new File('a.js'), ReporterInterface::STATE_BUILT)->shouldBeCalled();
            $reporter->reportOutputFile(new File('a.js'))->shouldBeCalled();

            $this->bundler->bundle($build_config);

            // check the output
            self::assertJsonFileEqualsJsonFile(__DIR__ . '/stdin.expected.json', __DIR__ . '/out/stdin.json');
            self::assertSame([
                '--debug',
                '--log-json',
                '--stdin',
                __DIR__ . '/var-saved/build_config.json',
            ], json_decode(file_get_contents(__DIR__ . '/out/args.json'), true));
        } finally {
            $fs = new Filesystem();
            $fs->remove([__DIR__ . '/var', __DIR__ . '/out']);
        }
    }

    public function testBundleWithNoFiles(): void
    {
        $fs = new Filesystem();

        try {
            $fs->dumpFile(__DIR__ . '/dist/require.js', 'foo');
            $fs->dumpFile(
                __DIR__ . '/var/52/689af_dist.require.js.sources',
                'a:1:{i:0;s:31:"../../src/Builder/js/require.js";}'
            );

            // Fake node by using PHP and making the build script a php file.
            $node     = new Executable('php', '');
            $reporter = $this->prophesize(ReporterInterface::class);

            $this->config->getOutputFolder()->willReturn('dist');
            $this->config->getOutputFolder(true)->willReturn('dist');
            $this->config->getCacheDir()->willReturn(__DIR__ . '/var');
            $this->config->getSourceRoot()->willReturn('fixtures');
            $this->config->getProjectRoot()->willReturn(__DIR__);
            $this->config->isDev()->willReturn(true);
            $this->config->getReporter()->willReturn($reporter);
            $this->config->getEntryPoints()->willReturn([]);
            $this->config->getAssetFiles()->willReturn([]);
            $this->config->getSplitStrategy()->willReturn(new OneOnOneSplittingStrategy());
            $this->config->getNodeJsExecutable()->willReturn($node);

            $build_config = new BuildConfig($this->config->reveal());
            $build_config->registerStep(new IdentityBuildStep('.js'));
            $build_config->registerWriter(new GenericFileWriter());

            $fs->dumpFile(__DIR__ . '/var/build_config.json', json_encode([
                'checksum' => $build_config->calculateHash(),
                'mapping'  => ['.js' => '.js'],
            ]));

            $this->bundler->bundle($build_config);

            // check the output
            self::assertFileDoesNotExist(__DIR__ . '/out/args.json');
            self::assertFileDoesNotExist(__DIR__ . '/out/stdin.json');
        } finally {
            $fs->remove([__DIR__ . '/var', __DIR__ . '/out', __DIR__ . '/dist']);
        }
    }

    public function testBundleBuildError(): void
    {
        $bundler = new Bundler(
            $this->finder->reveal(),
            $this->config->reveal(),
            __DIR__ . '/mock_broken_builder.php'
        );

        try {
            // Fake node by using PHP and making the build script a php file.
            $node     = new Executable('php', '');
            $reporter = $this->prophesize(ReporterInterface::class);

            $this->config->getOutputFolder()->willReturn('dist');
            $this->config->getOutputFolder(true)->willReturn('dist');
            $this->config->getCacheDir()->willReturn(__DIR__ . '/var');
            $this->config->getSourceRoot()->willReturn('fixtures');
            $this->config->getProjectRoot()->willReturn(__DIR__);
            $this->config->isDev()->willReturn(true);
            $this->config->getReporter()->willReturn($reporter);
            $this->config->getEntryPoints()->willReturn([
                'foo.js',
            ]);
            $this->config->getAssetFiles()->willReturn([
                'bar.js',
            ]);
            $this->config->getSplitStrategy()->willReturn(new OneOnOneSplittingStrategy());
            $this->config->getNodeJsExecutable()->willReturn($node);

            $this->finder->all(new File('fixtures/foo.js'))->willReturn(new RootFile(new File('fixtures/foo.js')));
            $this->finder->all(new File('fixtures/bar.js'))->willReturn(new RootFile(new File('fixtures/bar.js')));

            $build_config = new BuildConfig($this->config->reveal());
            $build_config->registerStep(new IdentityBuildStep('.js'));
            $build_config->registerWriter(new GenericFileWriter());

            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Cannot compile due to compiler error. Output: FOO');
            $bundler->bundle($build_config);
        } finally {
            $fs = new Filesystem();
            $fs->remove([__DIR__ . '/var', __DIR__ . '/out']);
        }
    }
}

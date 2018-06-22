<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

use Hostnet\Component\Resolver\Config\ConfigInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Builder\BuildConfig
 */
class BuildConfigTest extends TestCase
{
    private $config;

    protected function setUp()
    {
        $this->config = $this->prophesize(ConfigInterface::class);

        $this->config->getProjectRoot()->willReturn(__DIR__);
        $this->config->getOutputFolder(true)->willReturn('foo/bar');
        $this->config->getCacheDir()->willReturn('/tmp');
        $this->config->isDev()->willReturn(true);
    }

    public function testConstruct(): void
    {
        $build_config = new BuildConfig($this->config->reveal());
        $build_config->compile();

        $data = $build_config->jsonSerialize();

        self::assertSame([
            'root' => __DIR__ . DIRECTORY_SEPARATOR,
            'out' => 'foo/bar' . DIRECTORY_SEPARATOR,
            'cache' => '/tmp' . DIRECTORY_SEPARATOR,
        ], $data['paths']);
        self::assertSame([], $data['mapping']);
        self::assertSame([], $data['build']);
    }

    public function testConstructNonDev(): void
    {
        $config = $this->prophesize(ConfigInterface::class);

        $config->getProjectRoot()->willReturn(__DIR__);
        $config->getOutputFolder(true)->willReturn('foo/bar');
        $config->getCacheDir()->willReturn('/tmp');
        $config->isDev()->willReturn(false);

        $build_config = new BuildConfig($config->reveal());
        $build_config->compile();

        $data = $build_config->jsonSerialize();

        self::assertSame([
            'root' => __DIR__ . DIRECTORY_SEPARATOR,
            'out' => 'foo/bar' . DIRECTORY_SEPARATOR,
        ], $data['paths']);
        self::assertSame([], $data['mapping']);
        self::assertSame([], $data['build']);
    }

    public function testCompile(): void
    {
        $step1 = $this->makeBuildStep(
            '.js',
            [AbstractBuildStep::FILE_READ],
            '.js',
            AbstractBuildStep::FILE_TRANSPILED,
            'phpunit1'
        );
        $step2 = $this->makeBuildStep(
            '.js',
            [AbstractBuildStep::FILE_TRANSPILED],
            '.js',
            AbstractBuildStep::FILE_READY,
            'phpunit2'
        );
        $step3 = $this->makeBuildStep(
            '.js',
            [AbstractBuildStep::FILE_READY],
            '.js',
            AbstractBuildStep::FILE_READY,
            'phpunit3'
        );
        $step4 = $this->makeBuildStep(
            '.js',
            [AbstractBuildStep::FILE_READY],
            '.js',
            AbstractBuildStep::FILE_READY,
            'phpunit4',
            75
        );
        $step5 = $this->makeBuildStep(
            '.js',
            [AbstractBuildStep::MODULES_COLLECTED],
            '.js',
            AbstractBuildStep::MODULES_READY,
            'phpunit5'
        );

        $writer1 = $this->makeWriter('.js', 'out1');
        $writer2 = $this->makeWriter('.js', 'out2');

        $build_config = new BuildConfig($this->config->reveal());
        $build_config->registerStep($step1);
        $build_config->registerStep($step2);
        $build_config->registerStep($step3);
        $build_config->registerStep($step4);
        $build_config->registerStep($step5);
        $build_config->registerWriter($writer1);
        $build_config->registerWriter($writer2);
        $build_config->compile();

        $data = $build_config->jsonSerialize();

        self::assertSame([
            'root' => __DIR__ . DIRECTORY_SEPARATOR,
            'out' => 'foo/bar' . DIRECTORY_SEPARATOR,
            'cache' => '/tmp' . DIRECTORY_SEPARATOR,
        ], $data['paths']);
        self::assertSame([
            '.js' => '.js',
        ], $data['mapping']);
        self::assertSame([
            '.js' => [
                ['phpunit1', 'phpunit2', 'phpunit4', 'phpunit3'],
                ['phpunit5'],
                ['out1', 'out2'],
            ],
        ], $data['build']);
    }

    /**
     * In this test there are two option to get from FILE_READ to FILE_READY. The output should favor the longest path.
     */
    public function testCompileLongestPath(): void
    {
        $step1 = $this->makeBuildStep(
            '.js',
            [AbstractBuildStep::FILE_READ],
            '.js',
            AbstractBuildStep::FILE_READY,
            'short'
        );
        $step2 = $this->makeBuildStep(
            '.js',
            [AbstractBuildStep::FILE_READ],
            '.js',
            AbstractBuildStep::FILE_TRANSPILED,
            'long a'
        );
        $step3 = $this->makeBuildStep(
            '.js',
            [AbstractBuildStep::FILE_TRANSPILED],
            '.js',
            AbstractBuildStep::FILE_READY,
            'long b'
        );

        $writer = $this->makeWriter('.js', 'out');

        $build_config = new BuildConfig($this->config->reveal());
        $build_config->registerStep($step1);
        $build_config->registerStep($step2);
        $build_config->registerStep($step3);
        $build_config->registerWriter($writer);
        $build_config->compile();

        $data = $build_config->jsonSerialize();

        self::assertSame([
            'root' => __DIR__ . DIRECTORY_SEPARATOR,
            'out' => 'foo/bar' . DIRECTORY_SEPARATOR,
            'cache' => '/tmp' . DIRECTORY_SEPARATOR,
        ], $data['paths']);
        self::assertSame([
            '.js' => '.js',
        ], $data['mapping']);
        self::assertSame([
            '.js' => [
                ['long a', 'long b'],
                [],
                ['out'],
            ],
        ], $data['build']);
    }

    /**
     * In this test there are two option to get from FILE_READ to FILE_READY. Both have the same length, the output
     * should favor the highest prio path.
     */
    public function testCompileLongestPathPrio(): void
    {
        $step1 = $this->makeBuildStep(
            '.js',
            [AbstractBuildStep::FILE_READ],
            '.js',
            AbstractBuildStep::FILE_READY,
            'low',
            10
        );
        $step2 = $this->makeBuildStep(
            '.js',
            [AbstractBuildStep::FILE_READ],
            '.js',
            AbstractBuildStep::FILE_READY,
            'high',
            20
        );

        $writer = $this->makeWriter('.js', 'out');

        $build_config = new BuildConfig($this->config->reveal());
        $build_config->registerStep($step1);
        $build_config->registerStep($step2);
        $build_config->registerWriter($writer);
        $build_config->compile();

        $data = $build_config->jsonSerialize();

        self::assertSame([
            'root' => __DIR__ . DIRECTORY_SEPARATOR,
            'out' => 'foo/bar' . DIRECTORY_SEPARATOR,
            'cache' => '/tmp' . DIRECTORY_SEPARATOR,
        ], $data['paths']);
        self::assertSame([
            '.js' => '.js',
        ], $data['mapping']);
        self::assertSame([
            '.js' => [
                ['high'],
                [],
                ['out'],
            ],
        ], $data['build']);
    }

    /**
     * In this test has four possible options from the FILE_READ state, two are self loops. The result should first
     * contain the self loops and then the highest priority of the transition. The self loops need to be in order of
     * priority.
     */
    public function testCompileSelfLoopPrio(): void
    {
        $step1 = $this->makeBuildStep(
            '.js',
            [AbstractBuildStep::FILE_READ],
            '.js',
            AbstractBuildStep::FILE_READY,
            'last a',
            10
        );
        $step2 = $this->makeBuildStep(
            '.js',
            [AbstractBuildStep::FILE_READ],
            '.js',
            AbstractBuildStep::FILE_READY,
            'last b',
            20
        );
        $step3 = $this->makeBuildStep(
            '.js',
            [AbstractBuildStep::FILE_READ],
            '.js',
            AbstractBuildStep::FILE_READ,
            'low',
            10
        );
        $step4 = $this->makeBuildStep(
            '.js',
            [AbstractBuildStep::FILE_READ],
            '.js',
            AbstractBuildStep::FILE_READ,
            'high',
            20
        );

        $writer = $this->makeWriter('.js', 'out');

        $build_config = new BuildConfig($this->config->reveal());
        $build_config->registerStep($step1);
        $build_config->registerStep($step2);
        $build_config->registerStep($step3);
        $build_config->registerStep($step4);
        $build_config->registerWriter($writer);
        $build_config->compile();

        $data = $build_config->jsonSerialize();

        self::assertSame([
            'root' => __DIR__ . DIRECTORY_SEPARATOR,
            'out' => 'foo/bar' . DIRECTORY_SEPARATOR,
            'cache' => '/tmp' . DIRECTORY_SEPARATOR,
        ], $data['paths']);
        self::assertSame([
            '.js' => '.js',
        ], $data['mapping']);
        self::assertSame([
            '.js' => [
                ['high', 'low', 'last b'],
                [],
                ['out'],
            ],
        ], $data['build']);
    }

    public function testCompileNoWriter(): void
    {
        $step = $this->makeBuildStep(
            '.js',
            [AbstractBuildStep::FILE_READ],
            '.js',
            AbstractBuildStep::FILE_READY,
            'last a',
            10
        );

        $build_config = new BuildConfig($this->config->reveal());
        $build_config->registerStep($step);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No writers configured for extension ".js".');
        $build_config->compile();
    }

    public function testCompileAlreadyCompiled(): void
    {
        $build_config = new BuildConfig($this->config->reveal());
        $build_config->compile();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot recompile already compiled build config.');
        $build_config->compile();
    }

    public function testCompileBadStep(): void
    {
        $step = $this->makeBuildStep(
            '.js',
            [AbstractBuildStep::FILE_READY],
            '.js',
            AbstractBuildStep::FILE_READ,
            'bad'
        );

        $build_config = new BuildConfig($this->config->reveal());
        $build_config->registerStep($step);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot go back in build states for ');
        $build_config->compile();
    }

    public function testRegisterStepForAlreadyCompiled(): void
    {
        $step = $this->makeBuildStep(
            '.js',
            [AbstractBuildStep::FILE_READY],
            '.js',
            AbstractBuildStep::FILE_READ,
            'bad'
        );

        $build_config = new BuildConfig($this->config->reveal());
        $build_config->compile();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Build config is already compiled and can no longer change.');
        $build_config->registerStep($step);
    }

    public function testRegisterWriterForAlreadyCompiled(): void
    {
        $writer = $this->makeWriter('.js', 'bad');

        $build_config = new BuildConfig($this->config->reveal());
        $build_config->compile();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Build config is already compiled and can no longer change.');
        $build_config->registerWriter($writer);
    }

    public function testCalculateHash(): void
    {
        $build_config1 = new BuildConfig($this->config->reveal());
        $build_config1->registerWriter($this->makeWriter('.js', 'out'));
        $build_config1->compile();

        $build_config2 = new BuildConfig($this->config->reveal());
        $build_config2->registerWriter($this->makeWriter('.js', 'out'));
        $build_config2->compile();

        self::assertSame($build_config1->calculateHash(), $build_config1->calculateHash());
        self::assertSame($build_config1->calculateHash(), $build_config2->calculateHash());
        self::assertSame($build_config2->calculateHash(), $build_config2->calculateHash());
    }

    public function testJsonSerializeForNotYetCompiled(): void
    {
        $build_config = new BuildConfig($this->config->reveal());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot serialize uncompiled build config.');
        $build_config->jsonSerialize();
    }

    public function testGetExtensionMap(): void
    {
        $step1 = $this->makeBuildStep(
            '.js',
            [AbstractBuildStep::FILE_READ],
            '.js',
            AbstractBuildStep::FILE_READY,
            'short'
        );
        $step2 = $this->makeBuildStep(
            '.js',
            [AbstractBuildStep::FILE_READ],
            '.js',
            AbstractBuildStep::FILE_TRANSPILED,
            'long a'
        );
        $step3 = $this->makeBuildStep(
            '.js',
            [AbstractBuildStep::FILE_TRANSPILED],
            '.js',
            AbstractBuildStep::FILE_READY,
            'long b'
        );


        $writer = $this->makeWriter('.js', 'out');

        $build_config = new BuildConfig($this->config->reveal());
        $build_config->registerStep($step1);
        $build_config->registerStep($step2);
        $build_config->registerStep($step3);
        $build_config->registerWriter($writer);
        $build_config->compile();

        self::assertEquals(new ExtensionMap(['.js' => '.js']), $build_config->getExtensionMap());
    }

    public function testGetExtensionMapForNotYetCompiled(): void
    {
        $build_config = new BuildConfig($this->config->reveal());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot get mapping for uncompiled build config.');
        $build_config->getExtensionMap();
    }

    public function testIsUpToDateWith(): void
    {
        $build_config = new BuildConfig($this->config->reveal());
        $build_config->registerWriter($this->makeWriter('.js', 'out'));
        $build_config->compile();

        self::assertFalse($build_config->isUpToDateWith(['checksum' => '1234']));
        self::assertTrue($build_config->isUpToDateWith(['checksum' => $build_config->calculateHash()]));
    }

    private function makeBuildStep(
        string $in_ext,
        array $in_states,
        string $out_ext,
        int $out_state,
        string $module,
        int $prio = 50
    ): AbstractBuildStep {
        $step = $this->prophesize(AbstractBuildStep::class);
        $step->acceptedExtension()->willReturn($in_ext);
        $step->acceptedStates()->willReturn($in_states);
        $step->resultingExtension()->willReturn($out_ext);
        $step->resultingState()->willReturn($out_state);
        $step->getJsModule()->willReturn($module);
        $step->buildPriority()->willReturn($prio);

        return $step->reveal();
    }

    private function makeWriter(string $in_ext, string $module): AbstractWriter
    {
        return new class ($in_ext, $module) extends AbstractWriter {
            private $in_ext;
            private $module;

            public function __construct(string $in_ext, string $module)
            {
                $this->in_ext = $in_ext;
                $this->module = $module;
            }

            public function acceptedExtension(): string
            {
                return $this->in_ext;
            }

            public function getJsModule(): string
            {
                return $this->module;
            }
        };
    }
}

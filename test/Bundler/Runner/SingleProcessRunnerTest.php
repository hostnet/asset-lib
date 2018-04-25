<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Bundler\Runner;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\TranspileException;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\ReaderInterface;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @covers \Hostnet\Component\Resolver\Bundler\Runner\SingleProcessRunner
 */
class SingleProcessRunnerTest extends TestCase
{
    private $config;
    private $reader;
    private $item;

    /**
     * @var SingleProcessRunner
     */
    private $runner;

    protected function setUp()
    {
        $this->config = $this->prophesize(ConfigInterface::class);
        $this->reader = $this->prophesize(ReaderInterface::class);
        $this->item   = new ContentItem(new File('/a'), 'a', $this->reader->reveal());

        $this->runner = new SingleProcessRunner($this->config->reveal(), ['bla' => '/blah.js']);
    }

    public function testExecuteUnknown()
    {
        $this->expectException(\DomainException::class);
        $this->runner->execute('baz', $this->item);
    }

    /**
     * @dataProvider executeProvider
     */
    public function testExecute(string $type, string $expected)
    {
        $this->config->getNodeJsExecutable()->willReturn(
            new Executable('echo', 'node_modules')
        );
        $this->config->getProjectRoot()->willReturn(__DIR__);

        $this->reader->read(Argument::type(File::class))->willReturn('wazzup');

        self::assertContains($expected, $this->runner->execute($type, $this->item));
    }

    public function executeProvider()
    {
        return [
            [RunnerType::TYPE_SCRIPT, 'tsc.js'],
            [RunnerType::LESS, 'lessc.js'],
            [RunnerType::UGLIFY, 'uglify.js'],
            [RunnerType::CLEAN_CSS, 'cleancss.js'],
            ['bla', '/blah.js'],
        ];
    }

    public function testUnsuccessfulProcess()
    {
        $this->config->getNodeJsExecutable()->willReturn(
            new Executable('if-this-command-exists-the-test-will-fail', 'node_modules')
        );

        $this->config->getProjectRoot()->willReturn(__DIR__);

        $this->reader->read(Argument::type(File::class))->willReturn('wazzup');

        $this->expectException(TranspileException::class);
        $this->runner->execute(RunnerType::UGLIFY, $this->item);
    }
}

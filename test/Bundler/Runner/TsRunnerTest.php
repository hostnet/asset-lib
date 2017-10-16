<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler\Runner;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\StringReader;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Bundler\Runner\TsRunner
 */
class TsRunnerTest extends TestCase
{
    /**
     * @var TsRunner
     */
    private $ts_runner;

    protected function setUp()
    {
        $this->ts_runner = new TsRunner(
            new Executable('echo', __DIR__)
        );
    }

    public function testExecute()
    {
        $item = new ContentItem(
            new File('foobar.ts'),
            'foobar.ts',
            new StringReader('')
        );

        $result = $this->ts_runner->execute($item);
        self::assertContains('tsc.js', $result);
    }

    /**
     * @expectedException \Hostnet\Component\Resolver\Bundler\TranspileException
     */
    public function testExecuteWriteError()
    {
        $item = new ContentItem(new File('foobar.ts'), 'foobar.ts', new StringReader(''));

        $listener = new TsRunner(new Executable('false', __DIR__));
        $listener->execute($item);
    }
}

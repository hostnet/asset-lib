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
 * @covers \Hostnet\Component\Resolver\Bundler\Runner\LessRunner
 */
class LessRunnerTest extends TestCase
{
    /**
     * @var LessRunner
     */
    private $less_runner;

    protected function setUp()
    {
        $this->less_runner = new LessRunner(
            new Executable('echo', __DIR__)
        );
    }

    public function testExecute()
    {
        $item = new ContentItem(
            new File('foobar.js'),
            'foobar.js',
            new StringReader('')
        );

        $result = $this->less_runner->execute($item, 'cwd');
        self::assertContains('lessc.js', $result);
    }

    /**
     * @expectedException \Hostnet\Component\Resolver\Bundler\TranspileException
     */
    public function testExecuteWriteError()
    {
        $item = new ContentItem(new File('foobar.js'), 'foobar.js', new StringReader(''));

        $listener = new LessRunner(new Executable('false', __DIR__));
        $listener->execute($item, 'cwd');
    }
}

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
 * @covers \Hostnet\Component\Resolver\Bundler\Runner\CleanCssRunner
 */
class CleanCssRunnerTest extends TestCase
{
    /**
     * @var CleanCssRunner
     */
    private $runner;

    protected function setUp()
    {
        $this->runner = new CleanCssRunner(
            new Executable('echo', __DIR__)
        );
    }

    public function testExecute()
    {
        $item = new ContentItem(
            new File('foobar.css'),
            'foobar.css',
            new StringReader('')
        );

        $result = $this->runner->execute($item);
        self::assertContains('cleancss.js', $result);
    }

    /**
     * @expectedException \Hostnet\Component\Resolver\Bundler\TranspileException
     */
    public function testExecuteWriteError()
    {
        $item = new ContentItem(new File('foobar.less'), 'foobar.less', new StringReader(''));

        $listener = new CleanCssRunner(new Executable('false', __DIR__));
        $listener->execute($item);
    }
}

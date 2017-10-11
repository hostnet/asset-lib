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
 * @covers \Hostnet\Component\Resolver\Bundler\Runner\UglifyJsRunner
 */
class UglifyJsRunnerTest extends TestCase
{
    /**
     * @var UglifyJsRunner
     */
    private $uglify_js_runner;

    protected function setUp()
    {
        $this->uglify_js_runner = new UglifyJsRunner(
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

        $result = $this->uglify_js_runner->execute($item);
        self::assertContains('uglify.js', $result);
    }

    /**
     * @expectedException \Hostnet\Component\Resolver\Bundler\TranspileException
     */
    public function testExecuteWriteError()
    {
        $item = new ContentItem(new File('foobar.js'), 'foobar.js', new StringReader(''));

        $listener = new UglifyJsRunner(new Executable('false', __DIR__));
        $listener->execute($item);
    }
}

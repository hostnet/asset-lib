<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler\Processor;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Bundler\Pipeline\FileReader;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Bundler\Processor\TsContentProcessor
 */
class TsContentProcessorTest extends TestCase
{
    /**
     * @var TsContentProcessor
     */
    private $ts_content_processor;

    protected function setUp()
    {
        $this->ts_content_processor = new TsContentProcessor(new Executable('echo', __DIR__));
    }

    public function testSupports()
    {
        self::assertTrue($this->ts_content_processor->supports(new ContentState('ts')));
        self::assertFalse($this->ts_content_processor->supports(new ContentState('ts', ContentState::PROCESSED)));
        self::assertFalse($this->ts_content_processor->supports(new ContentState('ts', ContentState::READY)));
        self::assertFalse($this->ts_content_processor->supports(new ContentState('js')));
        self::assertFalse($this->ts_content_processor->supports(new ContentState('php')));
        self::assertFalse($this->ts_content_processor->supports(new ContentState('json')));
    }

    public function testPeek()
    {
        $state = new ContentState('php');
        $this->ts_content_processor->peek(__DIR__, $state);

        self::assertSame('js', $state->extension());
        self::assertSame(ContentState::PROCESSED, $state->current());
    }

    public function testTranspile()
    {
        $item = new ContentItem(new File(basename(__FILE__)), 'foobar.ts', new FileReader(__DIR__));

        $this->ts_content_processor->transpile(__DIR__, $item);

        self::assertContains('js/tsc.js', $item->getContent());
        self::assertSame('foobar', $item->module_name);
        self::assertSame(ContentState::PROCESSED, $item->getState()->current());
    }

    /**
     * @expectedException \Hostnet\Component\Resolver\Bundler\TranspileException
     */
    public function testTranspileBad()
    {
        $processor = new TsContentProcessor(new Executable('false', __DIR__));

        $item = new ContentItem(new File(basename(__FILE__)), 'foobar', new FileReader(__DIR__));

        $processor->transpile(__DIR__, $item);
    }
}

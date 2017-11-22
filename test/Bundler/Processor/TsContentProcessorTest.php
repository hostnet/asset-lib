<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler\Processor;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Bundler\Runner\RunnerInterface;
use Hostnet\Component\Resolver\Bundler\Runner\RunnerType;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\FileReader;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Bundler\Processor\TsContentProcessor
 */
class TsContentProcessorTest extends TestCase
{
    private $ts_runner;

    /**
     * @var TsContentProcessor
     */
    private $ts_content_processor;

    protected function setUp()
    {
        $this->ts_runner = $this->prophesize(RunnerInterface::class);

        $this->ts_content_processor = new TsContentProcessor(
            $this->ts_runner->reveal()
        );
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

    /**
     * @dataProvider transpileProvider
     */
    public function testTranspile(string $original_name, string $expected_name)
    {
        $item = new ContentItem(new File(basename(__FILE__)), $original_name, new FileReader(__DIR__));

        $this->ts_runner->execute(RunnerType::TYPE_SCRIPT, $item)->willReturn('ts code');

        $this->ts_content_processor->transpile(__DIR__, $item);

        self::assertContains('ts code', $item->getContent());
        self::assertSame($expected_name, $item->module_name);
        self::assertSame(ContentState::PROCESSED, $item->getState()->current());
    }

    public function transpileProvider()
    {
        return [
            ['foobar.ts', 'foobar'],
            ['ks-swiper.module', 'ks-swiper.module']
        ];
    }
}

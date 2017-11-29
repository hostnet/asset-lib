<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler\Processor;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\StringReader;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Bundler\Processor\ModuleProcessor
 */
class ModuleProcessorTest extends TestCase
{
    /**
     * @var ModuleProcessor
     */
    private $module_processor;

    protected function setUp()
    {
        $this->module_processor = new ModuleProcessor();
    }

    public function testSupports()
    {
        self::assertTrue($this->module_processor->supports(new ContentState('js', ContentState::PROCESSED)));
        self::assertFalse($this->module_processor->supports(new ContentState('js', ContentState::READY)));
        self::assertFalse($this->module_processor->supports(new ContentState('js')));
        self::assertFalse($this->module_processor->supports(new ContentState('css')));
        self::assertFalse($this->module_processor->supports(new ContentState('php')));
        self::assertFalse($this->module_processor->supports(new ContentState('json')));
    }

    public function testPeek()
    {
        $state = new ContentState('js');
        $this->module_processor->peek(__DIR__, $state);

        self::assertSame('js', $state->extension());
        self::assertSame(ContentState::READY, $state->current());
    }

    public function testTranspile()
    {
        $item = new ContentItem(
            new File(basename(__FILE__)),
            'bar/a"/\'/foobar.js',
            new StringReader('console.log("foobar");')
        );

        $this->module_processor->transpile(__DIR__, $item);

        self::assertStringEqualsFile(__DIR__ . '/expected.module.js', $item->getContent());
        self::assertSame('bar/a"/\'/foobar.js', $item->module_name);
        self::assertSame(ContentState::READY, $item->getState()->current());
    }

    public function testTranspileNoXSS()
    {
        $item = new ContentItem(new File(basename(__FILE__)), 'foobar""js', new StringReader('console.log("foobar");'));

        $this->module_processor->transpile(__DIR__, $item);

        self::assertStringEqualsFile(__DIR__ . '/expected.module2.js', $item->getContent());
        self::assertSame('foobar""js', $item->module_name);
        self::assertSame(ContentState::READY, $item->getState()->current());
    }
}

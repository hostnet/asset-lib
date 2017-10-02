<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler\Processor;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Bundler\Pipeline\StringReader;
use Hostnet\Component\Resolver\File;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Bundler\Processor\JsonProcessor
 */
class JsonProcessorTest extends TestCase
{
    /**
     * @var JsonProcessor
     */
    private $json_processor;

    protected function setUp()
    {
        $this->json_processor = new JsonProcessor();
    }

    public function testSupports()
    {
        self::assertTrue($this->json_processor->supports(new ContentState('json')));
        self::assertFalse($this->json_processor->supports(new ContentState('json', ContentState::PROCESSED)));
        self::assertFalse($this->json_processor->supports(new ContentState('json', ContentState::READY)));
        self::assertFalse($this->json_processor->supports(new ContentState('css')));
        self::assertFalse($this->json_processor->supports(new ContentState('php')));
        self::assertFalse($this->json_processor->supports(new ContentState('js')));
    }

    public function testPeek()
    {
        $state = new ContentState('json');
        $this->json_processor->peek(__DIR__, $state);

        self::assertSame('js', $state->extension());
        self::assertSame(ContentState::PROCESSED, $state->current());
    }

    public function testTranspile()
    {
        $item = new ContentItem(new File(basename(__FILE__)), 'foobar.json', new StringReader('{foo: "bar"}'));

        $this->json_processor->transpile(__DIR__, $item);

        self::assertSame("return {foo: \"bar\"};\n", $item->getContent());
        self::assertSame('foobar.json', $item->module_name);
        self::assertSame(ContentState::PROCESSED, $item->getState()->current());
    }
}

<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler\Processor;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileSystem\FileReader;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Bundler\Processor\LessContentProcessor
 */
class LessContentProcessorTest extends TestCase
{
    /**
     * @var LessContentProcessor
     */
    private $less_content_processor;

    protected function setUp()
    {
        $this->less_content_processor = new LessContentProcessor(new Executable('echo', __DIR__));
    }

    public function testSupports()
    {
        self::assertTrue($this->less_content_processor->supports(new ContentState('less')));
        self::assertFalse($this->less_content_processor->supports(new ContentState('less', ContentState::PROCESSED)));
        self::assertFalse($this->less_content_processor->supports(new ContentState('less', ContentState::READY)));
        self::assertFalse($this->less_content_processor->supports(new ContentState('css')));
        self::assertFalse($this->less_content_processor->supports(new ContentState('php')));
        self::assertFalse($this->less_content_processor->supports(new ContentState('json')));
    }

    public function testPeek()
    {
        $state = new ContentState('less');
        $this->less_content_processor->peek(__DIR__, $state);

        self::assertSame('css', $state->extension());
        self::assertSame(ContentState::READY, $state->current());
    }

    /**
     * @dataProvider transpileProvider
     */
    public function testTranspile(string $path, string $cwd, string $target_path)
    {
        $item = new ContentItem(new File($path), 'foobar.less', new FileReader($cwd));

        $this->less_content_processor->transpile($cwd, $item);

        self::assertSame('foobar.less', $item->module_name);
        self::assertContains('js' . DIRECTORY_SEPARATOR . 'lessc.js', $item->getContent());
        self::assertContains(' ' . $target_path, $item->getContent());
        self::assertSame(ContentState::READY, $item->getState()->current());
    }

    public function transpileProvider()
    {
        $clean = File::clean(__FILE__);

        return [
            [basename(__FILE__), __DIR__, $clean],
            [__FILE__, __DIR__, $clean],
        ];
    }

    /**
     * @expectedException \Hostnet\Component\Resolver\Bundler\TranspileException
     */
    public function testTranspileBad()
    {
        $processor = new LessContentProcessor(new Executable('false', __DIR__));

        $item = new ContentItem(new File(basename(__FILE__)), 'foobar', new FileReader(__DIR__));

        $processor->transpile(__DIR__, $item);
    }
}

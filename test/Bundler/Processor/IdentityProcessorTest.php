<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler\Processor;

use Hostnet\Component\Resolver\Bundler\ContentItem;
use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\FileSystem\FileReader;
use Hostnet\Component\Resolver\File;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Bundler\Processor\IdentityProcessor
 */
class IdentityProcessorTest extends TestCase
{
    private $extension;

    /**
     * @var IdentityProcessor
     */
    private $identity_processor;

    protected function setUp()
    {
        $this->extension  = 'foo';

        $this->identity_processor = new IdentityProcessor($this->extension);
    }

    public function testSupports()
    {
        self::assertTrue($this->identity_processor->supports(new ContentState('foo')));
        self::assertFalse($this->identity_processor->supports(new ContentState('foo', ContentState::PROCESSED)));
        self::assertFalse($this->identity_processor->supports(new ContentState('foo', ContentState::READY)));
        self::assertFalse($this->identity_processor->supports(new ContentState('css')));
        self::assertFalse($this->identity_processor->supports(new ContentState('php')));
        self::assertFalse($this->identity_processor->supports(new ContentState('json')));
    }

    public function testPeek()
    {
        $state = new ContentState('foo');
        $this->identity_processor->peek(__DIR__, $state);

        self::assertSame('foo', $state->extension());
        self::assertSame(ContentState::READY, $state->current());
    }

    public function testTranspile()
    {
        $item = new ContentItem(new File(basename(__FILE__)), 'foobar', new FileReader(__DIR__));
        $this->identity_processor->transpile(__DIR__, $item);

        self::assertSame('php', $item->getState()->extension());
        self::assertSame(ContentState::READY, $item->getState()->current());
    }
}

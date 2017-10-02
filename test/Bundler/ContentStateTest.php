<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);
namespace Hostnet\Component\Resolver\Bundler;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Bundler\ContentState
 */
class ContentStateTest extends TestCase
{
    public function testGeneric()
    {
        $state = new ContentState('foo');

        self::assertSame('foo', $state->extension());
        self::assertSame(ContentState::UNPROCESSED, $state->current());
        self::assertFalse($state->isReady());

        $state->transition(ContentState::PROCESSED, 'js');

        self::assertSame('js', $state->extension());
        self::assertSame(ContentState::PROCESSED, $state->current());
        self::assertFalse($state->isReady());

        $state->transition(ContentState::READY, 'js');

        self::assertSame('js', $state->extension());
        self::assertSame(ContentState::READY, $state->current());
        self::assertTrue($state->isReady());
    }

    public function testGenericSameTransition()
    {
        $state = new ContentState('foo');
        $state->transition(ContentState::UNPROCESSED, 'js');

        self::assertSame('js', $state->extension());
        self::assertSame(ContentState::UNPROCESSED, $state->current());
        self::assertFalse($state->isReady());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cannot transition (from 2, to: 0) backwards.
     */
    public function testGenericBadTransition()
    {
        $state = new ContentState('foo', ContentState::READY);
        $state->transition(ContentState::UNPROCESSED, 'js');
    }
}

<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Cache;

use Hostnet\Component\Resolver\File;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Cache\Cache
 */
class CacheTest extends TestCase
{
    public function testGeneric()
    {
        $cache = new Cache();

        self::assertFalse($cache->has('foobar'));
        self::assertNull($cache->get('foobar'));
        self::assertSame('henk', $cache->get('foobar', 'henk'));

        $cache->set('foobar', 'barbaz');
        $cache->set('barbaz', 'phpunit');

        self::assertTrue($cache->has('foobar'));
        self::assertTrue($cache->has('barbaz'));
        self::assertSame('barbaz', $cache->get('foobar'));
        self::assertSame('phpunit', $cache->get('barbaz'));

        $cache->delete('foobar');

        self::assertFalse($cache->has('foobar'));
        self::assertTrue($cache->has('barbaz'));

        $cache->clear();

        self::assertFalse($cache->has('foobar'));
        self::assertFalse($cache->has('barbaz'));
    }

    public function testPersistence()
    {
        $cache1 = new Cache(__DIR__ . '/deps');
        $cache1->load();

        $cache1->set('foobar', 'barbaz');
        $cache1->set('barbaz', 'phpunit');
        $cache1->save();

        $cache2 = new Cache(__DIR__ . '/deps');
        $cache2->load();

        unlink(__DIR__ . '/deps');

        self::assertTrue($cache2->has('foobar'));
        self::assertTrue($cache2->has('barbaz'));
        self::assertSame('barbaz', $cache2->get('foobar'));
        self::assertSame('phpunit', $cache2->get('barbaz'));
    }

    public function testCreateFileCacheKey()
    {
        self::assertSame('c3010_foo.js', Cache::createFileCacheKey(new File('foo.js')));
    }

    /**
     * @expectedException \Hostnet\Component\Resolver\Cache\Exception\InvalidArgumentException
     */
    public function testDeleteNoneExistingKey()
    {
        $cache = new Cache();
        $cache->delete('foobar');
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testGetMultiple()
    {
        $cache = new Cache();
        $cache->getMultiple([]);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testSetMultiple()
    {
        $cache = new Cache();
        $cache->setMultiple([]);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testDeleteMultiple()
    {
        $cache = new Cache();
        $cache->deleteMultiple([]);
    }
}

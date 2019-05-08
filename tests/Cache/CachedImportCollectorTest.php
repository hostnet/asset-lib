<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Cache;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\ImportCollection;
use Hostnet\Component\Resolver\Import\ImportCollectorInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Cache\CachedImportCollector
 */
class CachedImportCollectorTest extends TestCase
{
    private $inner;
    private $cache;

    /**
     * @var CachedImportCollector
     */
    private $cached_import_collector;

    protected function setUp(): void
    {
        $this->inner = $this->prophesize(ImportCollectorInterface::class);
        $this->cache = new Cache();

        $this->cached_import_collector = new CachedImportCollector(
            $this->inner->reveal(),
            $this->cache
        );
    }

    public function testSupports(): void
    {
        $file = new File('foo.js');

        $this->inner->supports($file)->willReturn(true)->shouldBeCalled();

        self::assertTrue($this->cached_import_collector->supports($file));
    }

    public function testCollectNoCache(): void
    {
        $file    = new File(basename(__FILE__));
        $imports = new ImportCollection();

        $this->inner->collect(__DIR__, $file, $imports)->shouldBeCalled();

        $this->cached_import_collector->collect(__DIR__, $file, $imports);
    }

    public function testCollectWithCache(): void
    {
        $file                           = new File(basename(__FILE__));
        $imports                        = new ImportCollection();
        $cached_imports                 = new ImportCollection();
        $cached_imports->addResource($f = new File('bar.js'));

        $this->cache->set(
            $file->path . get_class($this->inner->reveal()),
            ['deps' => $cached_imports, 'info' => filemtime(__FILE__)]
        );

        $this->inner->collect(__DIR__, $file, $imports)->shouldNotBeCalled();

        $this->cached_import_collector->collect(__DIR__, $file, $imports);

        self::assertEquals([$f], $cached_imports->getResources());
    }
}

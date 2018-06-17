<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\Dependency;
use Hostnet\Component\Resolver\Import\DependencyNodeInterface;
use Hostnet\Component\Resolver\Split\EntryPointSplittingStrategyInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Builder\EntryPoint
 */
class EntryPointTest extends TestCase
{
    public function testGeneric()
    {
        $file = new File(__FILE__);
        $dep1 = new Dependency(new File('some.file'));
        $dep2 = new Dependency(new File('other.file'), false, true);
        $dep3 = new Dependency(new File('node_modules/foo'));

        $dep = new Dependency($file);
        $dep->addChild($dep1);
        $dep->addChild($dep2);
        $dep->addChild($dep3);

        $resolve_strategy = new class implements EntryPointSplittingStrategyInterface {
            public function resolveChunk(string $entry_point, DependencyNodeInterface $dependency): ?string
            {
                return false === strpos($dependency->getFile()->path, 'node_modules')
                    ? 'file1.js'
                    : 'file2.js';
            }
        };

        $entry_point = new EntryPoint($dep, $resolve_strategy);

        self::assertEquals($file, $entry_point->getFile());
        $expected = [
            'output/file1.js' => [__FILE__, 'some.file'],
            'output/file2.js' => ['node_modules/foo'],
            'output/other.file' => ['other.file'],
        ];

        self::assertSame(
            $expected,
            array_map(
                function (array $dependencies) {
                    return array_map(
                        function (DependencyNodeInterface $dep) {
                            return $dep->getFile()->getName();
                        },
                        $dependencies
                    );
                },
                $entry_point->getFilesToBuild('output')
            )
        );
    }

    public function testWithDifferentExtension()
    {
        $file = new File('app.ts');
        $dep  = new Dependency($file);

        $resolve_strategy = new class implements EntryPointSplittingStrategyInterface {
            public function resolveChunk(string $entry_point, DependencyNodeInterface $dependency): ?string
            {
                return $entry_point;
            }
        };

        $entry_point = new EntryPoint($dep, $resolve_strategy);

        self::assertEquals($file, $entry_point->getFile());
        self::assertSame(
            ['output/app.ts' => [$dep]],
            $entry_point->getFilesToBuild('output')
        );
    }
}

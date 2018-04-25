<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import\Nodejs;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Hostnet\Component\Resolver\Import\Nodejs\Executable
 */
class ExecutableTest extends TestCase
{
    public function testGeneric()
    {
        $executable = new Executable('foo/node', 'foo/node_modules');

        self::assertSame('foo/node', $executable->getBinary());
        self::assertSame('foo/node_modules', $executable->getNodeModulesLocation());
    }
}

<?php
namespace Hostnet\Component\Resolver\Functional;

use Hostnet\Component\Resolver\Bundler\BundleFileWriter;
use Hostnet\Component\Resolver\Bundler\Item;
use Hostnet\Component\Resolver\Bundler\JsModuleWrapper;
use Hostnet\Component\Resolver\Bundler\Pipeline;
use Hostnet\Component\Resolver\Bundler\PipelineDispatcher;
use Hostnet\Component\Resolver\Bundler\SingleFileWriter;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\FileConfig;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Hostnet\Component\Resolver\Packer;
use Hostnet\Component\Resolver\Transpile\BuildIn\LessFileTranspiler;
use Hostnet\Component\Resolver\Transpile\BuildIn\TsFileTranspiler;
use Hostnet\Component\Resolver\Transpile\Transpiler;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class BundlerTest extends TestCase
{
    public function testBundler()
    {
        $this->markTestSkipped();

        $fixtures_folder = __DIR__ . '/../fixtures';

        `rm -rf $fixtures_folder/web/*`;

//        Packer::pack($fixtures_folder, new NullLogger());
        Packer::pack($fixtures_folder, new NullLogger(), true);
    }

    public function testPipeline()
    {
        $config = new FileConfig(true, __DIR__ . '/../fixtures/resolve.config.json');
        $nodejs = new Executable(
            $config->getNodeJsBinary(),
            $config->getNodeModulesPath()
        );

        $dispatcher = new PipelineDispatcher();

        $transpiler = new Transpiler($config->cwd());
        $transpiler->addTranspiler(new TsFileTranspiler($nodejs));
        $transpiler->addTranspiler(new LessFileTranspiler($nodejs));

        $wrapper = new JsModuleWrapper();

        $f = new File('resolver/ts/import-syntax/main.ts');
        $f2 = new File('resolver/less/import-syntax/main.less');

        $pipe = new Pipeline($dispatcher, $transpiler, $wrapper);
        $pipe->process(
            new Item($f, 'main', file_get_contents($config->cwd() . '/' . $f->path)),
            new SingleFileWriter(__DIR__ . '/t1.js', $dispatcher)
        );
        $pipe->process(
            $i = new Item($f2, 'main', file_get_contents($config->cwd() . '/' . $f2->path)),
            new BundleFileWriter(__DIR__ . '/t2.css', [$i], $dispatcher)
        );
    }
}

<?php

namespace Hostnet\Component\Resolver;

use Hostnet\Component\Resolver\Bundler\Bundler;
use Hostnet\Component\Resolver\Bundler\EntryPoint;
use Hostnet\Component\Resolver\Bundler\JsModuleWrapper;
use Hostnet\Component\Resolver\Import\BuildIn\AngularImportCollector;
use Hostnet\Component\Resolver\Import\BuildIn\JsImportCollector;
use Hostnet\Component\Resolver\Import\BuildIn\LessImportCollector;
use Hostnet\Component\Resolver\Import\BuildIn\TsImportCollector;
use Hostnet\Component\Resolver\Import\ImportFinder;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Hostnet\Component\Resolver\Import\Nodejs\FileResolver;
use Hostnet\Component\Resolver\Transform\BuildIn\AngularHtmlTransformer;
use Hostnet\Component\Resolver\Transform\BuildIn\UglifyJsTransformer;
use Hostnet\Component\Resolver\Transform\Transformer;
use Hostnet\Component\Resolver\Transpile\BuildIn\CssFileTranspiler;
use Hostnet\Component\Resolver\Transpile\BuildIn\HtmlFileTranspiler;
use Hostnet\Component\Resolver\Transpile\BuildIn\JsFileTranspiler;
use Hostnet\Component\Resolver\Transpile\BuildIn\LessFileTranspiler;
use Hostnet\Component\Resolver\Transpile\BuildIn\TsFileTranspiler;
use Hostnet\Component\Resolver\Transpile\Transpiler;
use Psr\Log\LoggerInterface;

/**
 * Simple facade that registered JS, CSS, TS and LESS compilation and runs it
 * using the 'entry-points.json' file.
 */
final class Packer
{
    public static function pack(string $project_root, LoggerInterface $logger, bool $dev = false)
    {
        $config = new Config($project_root . '/resolve.config.json');

        $nodejs = new Executable(
            $config->cwd() . '/' . $config->get('node-bin'),
            $config->cwd() . '/' . $config->get('node_modules')
        );

        $js_collector = new JsImportCollector(
            new FileResolver($config->cwd(), ['.js', '.json', '.node'])
        );
        $less_collector = new LessImportCollector();
        $ts_collector = new TsImportCollector(
            new JsImportCollector(
                new FileResolver($config->cwd(), ['.ts', '.js', '.json', '.node'])
            ),
            new FileResolver($config->cwd(), ['.ts', '.d.ts', '.js', '.json', '.node'])
        );

        $finder = new ImportFinder($config->cwd());
        $finder->addCollector($js_collector);
        $finder->addCollector($less_collector);
        $finder->addCollector($ts_collector);
        $finder->addCollector(new AngularImportCollector());

        $wrapper = new JsModuleWrapper($finder, new FileResolver($config->cwd(), ['.js', '.json', '.node']));

        $transpiler = new Transpiler($config->cwd());
        $transpiler->addTranspiler(new CssFileTranspiler());
        $transpiler->addTranspiler(new HtmlFileTranspiler());
        $transpiler->addTranspiler(new JsFileTranspiler());
        $transpiler->addTranspiler(new LessFileTranspiler($nodejs));
        $transpiler->addTranspiler(new TsFileTranspiler($nodejs));

        $transformer = new Transformer($config->cwd());
        $transformer->addTransformer(Transformer::POST_TRANSPILE, new AngularHtmlTransformer());

        if (!$dev) {
            $transformer->addTransformer(
                Transformer::PRE_WRITE,
                new UglifyJsTransformer($nodejs, $project_root . '/var/assets')
            );
        }

        $bundler = new Bundler(
            $config->cwd(),
            $transpiler,
            $transformer,
            $wrapper,
            $logger,
            $config->getWebRoot(),
            $config->getOutputFolder($dev),
            $project_root . '/var/assets',
            $dev
        );
        $bundler->bundle(array_map(function (string $file_name) use ($finder) {
            $file = new File($file_name);

            return new EntryPoint($file, $finder->all($file));
        }, $config->getEntryPoints()));
        $bundler->compile(array_map(function (string $file_name) use ($finder) {
            $file = new File($file_name);

            return new EntryPoint($file, $finder->all($file));
        }, $config->getAssetFiles()));
    }
}

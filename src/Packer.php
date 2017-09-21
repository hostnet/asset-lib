<?php

namespace Hostnet\Component\Resolver;

use Hostnet\Component\Resolver\Bundler\Bundler;
use Hostnet\Component\Resolver\Bundler\JsModuleWrapper;
use Hostnet\Component\Resolver\Cache\Cache;
use Hostnet\Component\Resolver\Cache\CachedImportCollector;
use Hostnet\Component\Resolver\Import\BuildIn\AngularImportCollector;
use Hostnet\Component\Resolver\Import\BuildIn\JsImportCollector;
use Hostnet\Component\Resolver\Import\BuildIn\LessImportCollector;
use Hostnet\Component\Resolver\Import\BuildIn\TsImportCollector;
use Hostnet\Component\Resolver\Import\ImportFinder;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Hostnet\Component\Resolver\Import\Nodejs\FileResolver;
use Hostnet\Component\Resolver\Transform\BuildIn\AngularHtmlTransformer;
use Hostnet\Component\Resolver\Transform\BuildIn\CleanCssTransformer;
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
        $config = new FileConfig($dev, $project_root . '/resolve.config.json');
        $cache = new Cache($config->getCacheDir() . '/dependencies');
        $cache->load();

        $nodejs = new Executable(
            $config->getNodeJsBinary(),
            $config->getNodeModulesPath()
        );

        $js_collector = new JsImportCollector(
            new FileResolver($config->cwd(), ['.js', '.json', '.node'])
        );

        if ($config->isDev()) {
            $js_collector = new CachedImportCollector($js_collector, $cache);
        }

        $finder = new ImportFinder($config->cwd());
        $finder->addCollector($js_collector);

        $wrapper = new JsModuleWrapper();

        $transpiler = new Transpiler($config->cwd());
        $transpiler->addTranspiler(new CssFileTranspiler());
        $transpiler->addTranspiler(new HtmlFileTranspiler());
        $transpiler->addTranspiler(new JsFileTranspiler());

        $transformer = new Transformer($config->cwd());

        // LESS
        if ($config->isLessEnabled()) {
            $less_collector = new LessImportCollector();
            if ($config->isDev()) {
                $less_collector = new CachedImportCollector($less_collector, $cache);
            }
            $finder->addCollector($less_collector);
            $transpiler->addTranspiler(new LessFileTranspiler($nodejs));
        }

        // TS
        if ($config->isTsEnabled()) {
            $ts_collector = new TsImportCollector(
                new JsImportCollector(
                    new FileResolver($config->cwd(), ['.ts', '.js', '.json', '.node'])
                ),
                new FileResolver($config->cwd(), ['.ts', '.d.ts', '.js', '.json', '.node'])
            );
            if ($config->isDev()) {
                $ts_collector = new CachedImportCollector($ts_collector, $cache);
            }
            $finder->addCollector($ts_collector);
            $transpiler->addTranspiler(new TsFileTranspiler($nodejs));
        }

        // ANGULAR
        if ($config->isAngularEnabled()) {
            $angular_collector = new AngularImportCollector();
            if ($config->isDev()) {
                $angular_collector = new CachedImportCollector($angular_collector, $cache);
            }
            $finder->addCollector($angular_collector);
            $transformer->addTransformer(Transformer::POST_TRANSPILE, new AngularHtmlTransformer());
        }

        if (!$config->isDev()) {
            $transformer->addTransformer(
                Transformer::PRE_WRITE,
                new UglifyJsTransformer($nodejs, $project_root . '/var/assets')
            );
            $transformer->addTransformer(
                Transformer::PRE_WRITE,
                new CleanCssTransformer($nodejs, $project_root . '/var/assets')
            );
        }

        $bundler = new Bundler(
            $finder,
            $transpiler,
            $transformer,
            $wrapper,
            $logger,
            $config
        );
        $bundler->execute();

        $cache->save();
    }
}

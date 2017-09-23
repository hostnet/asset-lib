<?php

namespace Hostnet\Component\Resolver;

use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Bundler\Pipeline\ContentPipeline;
use Hostnet\Component\Resolver\Bundler\PipelineBundler;
use Hostnet\Component\Resolver\Cache\Cache;
use Hostnet\Component\Resolver\Cache\CachedImportCollector;
use Hostnet\Component\Resolver\Event\AssetEvents;
use Hostnet\Component\Resolver\EventListener\AngularHtmlListener;
use Hostnet\Component\Resolver\EventListener\CleanCssListener;
use Hostnet\Component\Resolver\EventListener\UglifyJsListener;
use Hostnet\Component\Resolver\Import\BuildIn\AngularImportCollector;
use Hostnet\Component\Resolver\Import\BuildIn\JsImportCollector;
use Hostnet\Component\Resolver\Import\BuildIn\LessImportCollector;
use Hostnet\Component\Resolver\Import\BuildIn\TsImportCollector;
use Hostnet\Component\Resolver\Import\ImportFinder;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Hostnet\Component\Resolver\Import\Nodejs\FileResolver;
use Hostnet\Component\Resolver\Transpile\BuildIn\IdentityTranspiler;
use Hostnet\Component\Resolver\Transpile\BuildIn\JsModuleWrapper;
use Hostnet\Component\Resolver\Transpile\BuildIn\LessFileTranspiler;
use Hostnet\Component\Resolver\Transpile\BuildIn\TsFileTranspiler;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

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

        $dispatcher = new EventDispatcher();

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

        $pipeline = new ContentPipeline($dispatcher, $logger, $config);

        $pipeline->addProcessor(new IdentityTranspiler('css'));
        $pipeline->addProcessor(new IdentityTranspiler('html'));
        $pipeline->addProcessor(new IdentityTranspiler('js', ContentState::PROCESSED));
        $pipeline->addProcessor(new JsModuleWrapper());

        // LESS
        if ($config->isLessEnabled()) {
            $less_collector = new LessImportCollector();
            if ($config->isDev()) {
                $less_collector = new CachedImportCollector($less_collector, $cache);
            }
            $finder->addCollector($less_collector);
            $pipeline->addProcessor(new LessFileTranspiler($nodejs));
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
            $pipeline->addProcessor(new TsFileTranspiler($nodejs));
        }

        // ANGULAR
        if ($config->isAngularEnabled()) {
            $angular_collector = new AngularImportCollector();
            if ($config->isDev()) {
                $angular_collector = new CachedImportCollector($angular_collector, $cache);
            }
            $finder->addCollector($angular_collector);

            $listener = new AngularHtmlListener();

            $dispatcher->addListener(AssetEvents::POST_TRANSPILE, [$listener, 'onPostTranspile']);
        }

        if (!$config->isDev()) {
            $uglify_listener = new UglifyJsListener($nodejs, $project_root . '/var/assets');
            $cleancss_listener = new CleanCssListener($nodejs, $project_root . '/var/assets');

            $dispatcher->addListener(AssetEvents::PRE_WRITE, [$uglify_listener, 'onPreWrite']);
            $dispatcher->addListener(AssetEvents::PRE_WRITE, [$cleancss_listener, 'onPreWrite']);
        }

        $bundler = new PipelineBundler(
            $finder,
            $pipeline,
            $logger,
            $config
        );
        $bundler->execute();

        if ($config->isDev()) {
            $cache->save();
        }
    }
}

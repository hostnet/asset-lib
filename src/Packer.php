<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver;

use Hostnet\Component\Resolver\Bundler\ContentState;
use Hostnet\Component\Resolver\Bundler\Pipeline\ContentPipeline;
use Hostnet\Component\Resolver\Bundler\PipelineBundler;
use Hostnet\Component\Resolver\Bundler\Processor\IdentityProcessor;
use Hostnet\Component\Resolver\Bundler\Processor\JsonProcessor;
use Hostnet\Component\Resolver\Bundler\Processor\LessContentProcessor;
use Hostnet\Component\Resolver\Bundler\Processor\ModuleProcessor;
use Hostnet\Component\Resolver\Bundler\Processor\TsContentProcessor;
use Hostnet\Component\Resolver\Bundler\Runner\CleanCssRunner;
use Hostnet\Component\Resolver\Bundler\Runner\LessRunner;
use Hostnet\Component\Resolver\Bundler\Runner\TsRunner;
use Hostnet\Component\Resolver\Bundler\Runner\UglifyJsRunner;
use Hostnet\Component\Resolver\Cache\Cache;
use Hostnet\Component\Resolver\Cache\CachedImportCollector;
use Hostnet\Component\Resolver\Event\AssetEvents;
use Hostnet\Component\Resolver\EventListener\AngularHtmlListener;
use Hostnet\Component\Resolver\EventListener\CleanCssListener;
use Hostnet\Component\Resolver\EventListener\UglifyJsListener;
use Hostnet\Component\Resolver\FileSystem\FileReader;
use Hostnet\Component\Resolver\FileSystem\FileWriter;
use Hostnet\Component\Resolver\Import\BuiltIn\AngularImportCollector;
use Hostnet\Component\Resolver\Import\BuiltIn\JsImportCollector;
use Hostnet\Component\Resolver\Import\BuiltIn\LessImportCollector;
use Hostnet\Component\Resolver\Import\BuiltIn\TsImportCollector;
use Hostnet\Component\Resolver\Import\ImportFinder;
use Hostnet\Component\Resolver\Import\Nodejs\Executable;
use Hostnet\Component\Resolver\Import\Nodejs\FileResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Simple facade that registered JS, CSS, TS and LESS compilation and runs it
 * using the 'entry-points.json' file.
 */
final class Packer
{
    public static function pack(string $project_root, LoggerInterface $logger, bool $dev = false): void
    {
        $config = new FileConfig($dev, $project_root . '/resolve.config.json');
        $cache  = new Cache($config->getCacheDir() . '/dependencies');
        $cache->load();

        $dispatcher = new EventDispatcher();

        $nodejs = new Executable(
            $config->getNodeJsBinary(),
            $config->getNodeModulesPath()
        );

        $js_collector = new JsImportCollector(
            new FileResolver($config, ['.js', '.json', '.node'])
        );

        if ($config->isDev()) {
            $js_collector = new CachedImportCollector($js_collector, $cache);
        }

        $finder = new ImportFinder($config->cwd());
        $finder->addCollector($js_collector);

        $pipeline = new ContentPipeline($dispatcher, $logger, $config);

        $pipeline->addProcessor(new IdentityProcessor('css'));
        $pipeline->addProcessor(new IdentityProcessor('html'));
        $pipeline->addProcessor(new IdentityProcessor('js', ContentState::PROCESSED));
        $pipeline->addProcessor(new ModuleProcessor());
        $pipeline->addProcessor(new JsonProcessor());

        // LESS
        if ($config->isLessEnabled()) {
            $less_collector = new LessImportCollector();
            if ($config->isDev()) {
                $less_collector = new CachedImportCollector($less_collector, $cache);
            }
            $finder->addCollector($less_collector);
            $pipeline->addProcessor(new LessContentProcessor(new LessRunner($nodejs, $config)));
        }

        // TS
        if ($config->isTsEnabled()) {
            $ts_collector = new TsImportCollector(
                new JsImportCollector(
                    new FileResolver($config, ['.ts', '.js', '.json', '.node'])
                ),
                new FileResolver($config, ['.ts', '.d.ts', '.js', '.json', '.node'])
            );
            if ($config->isDev()) {
                $ts_collector = new CachedImportCollector($ts_collector, $cache);
            }
            $finder->addCollector($ts_collector);
            $pipeline->addProcessor(new TsContentProcessor(new TsRunner($nodejs)));
        }

        // ANGULAR
        if ($config->isAngularEnabled()) {
            $angular_collector = new AngularImportCollector();
            if ($config->isDev()) {
                $angular_collector = new CachedImportCollector($angular_collector, $cache);
            }
            $finder->addCollector($angular_collector);

            $listener = new AngularHtmlListener($config, $pipeline, $finder);

            $dispatcher->addListener(AssetEvents::POST_PROCESS, [$listener, 'onPostTranspile']);
        }

        $uglify_runner = new UglifyJsRunner($nodejs);

        if (!$config->isDev()) {
            $uglify_listener   = new UglifyJsListener($uglify_runner);
            $cleancss_listener = new CleanCssListener(new CleanCssRunner($nodejs));

            $dispatcher->addListener(AssetEvents::READY, [$uglify_listener, 'onPreWrite']);
            $dispatcher->addListener(AssetEvents::READY, [$cleancss_listener, 'onPreWrite']);
        }

        $bundler = new PipelineBundler(
            $finder,
            $pipeline,
            $logger,
            $config,
            $uglify_runner
        );
        $bundler->execute(new FileReader($config->cwd()), new FileWriter($config->cwd()));

        if ($config->isDev()) {
            $cache->save();
        }
    }
}

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
use Hostnet\Component\Resolver\Bundler\Processor\ModuleProcessor;
use Hostnet\Component\Resolver\Bundler\Runner\CleanCssRunner;
use Hostnet\Component\Resolver\Bundler\Runner\UglifyJsRunner;
use Hostnet\Component\Resolver\Cache\Cache;
use Hostnet\Component\Resolver\Cache\CachedImportCollector;
use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\Event\AssetEvents;
use Hostnet\Component\Resolver\EventListener\CleanCssListener;
use Hostnet\Component\Resolver\EventListener\UglifyJsListener;
use Hostnet\Component\Resolver\FileSystem\FileReader;
use Hostnet\Component\Resolver\FileSystem\FileWriter;
use Hostnet\Component\Resolver\Import\BuiltIn\JsImportCollector;
use Hostnet\Component\Resolver\Import\ImportFinder;
use Hostnet\Component\Resolver\Import\Nodejs\FileResolver;
use Hostnet\Component\Resolver\Plugin\PluginApi;

/**
 * Simple facade that registered JS, CSS, TS and LESS compilation and runs it
 * using the 'entry-points.json' file.
 */
final class Packer
{
    public static function pack(ConfigInterface $config): void
    {
        $cache = new Cache($config->getCacheDir() . '/dependencies');
        $cache->load();

        $dispatcher = $config->getEventDispatcher();

        $nodejs = $config->getNodeJsExecutable();

        $logger = $config->getLogger();

        $js_collector = new JsImportCollector(
            new FileResolver($config, ['.js', '.json', '.node'])
        );

        if ($config->isDev()) {
            $js_collector = new CachedImportCollector($js_collector, $cache);
        }

        $finder = new ImportFinder($config->getProjectRoot());
        $finder->addCollector($js_collector);

        $writer   = new FileWriter($config->getProjectRoot());
        $pipeline = new ContentPipeline($dispatcher, $logger, $config, $writer);

        $pipeline->addProcessor(new IdentityProcessor('css'));
        $pipeline->addProcessor(new IdentityProcessor('html'));
        $pipeline->addProcessor(new IdentityProcessor('js', ContentState::PROCESSED));
        $pipeline->addProcessor(new ModuleProcessor());
        $pipeline->addProcessor(new JsonProcessor());

        $plugin_api = new PluginApi($pipeline, $finder, $config, $cache);

        foreach ($config->getPlugins() as $plugin) {
            $plugin->activate($plugin_api);
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
        $bundler->execute(new FileReader($config->getProjectRoot()), $writer);

        if ($config->isDev()) {
            $cache->save();
        }
    }
}

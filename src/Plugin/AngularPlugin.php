<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

use Hostnet\Component\Resolver\Cache\CachedImportCollector;
use Hostnet\Component\Resolver\Event\AssetEvents;
use Hostnet\Component\Resolver\EventListener\AngularHtmlListener;
use Hostnet\Component\Resolver\Import\BuiltIn\AngularImportCollector;

/**
 * Parses Angular2.0+ templates and adds dependencies for templates and stylesheets.
 */
final class AngularPlugin implements PluginInterface
{
    public function activate(PluginApi $plugin_api): void
    {
        $angular_collector = new AngularImportCollector();
        if ($plugin_api->getConfig()->isDev()) {
            $angular_collector = new CachedImportCollector($angular_collector, $plugin_api->getCache());
        }
        $plugin_api->addCollector($angular_collector);
        $plugin_api->getConfig()->getEventDispatcher()->addListener(
            AssetEvents::POST_PROCESS,
            [new AngularHtmlListener($plugin_api), 'onPostTranspile']
        );
    }
}

<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

/**
 * Asset lib plugin interface. Any plugin should implement this interface.
 *
 * Example: adding a class that imports asset dependencies.
 * ```
 * $angular_collector = new AngularImportCollector();
 * if ($plugin_api->getConfig()->isDev()) {
 *     $angular_collector = new CachedImportCollector($angular_collector, $plugin_api->getCache());
 * }
 * $plugin_api->addCollector($angular_collector);
 * ```
 *
 * Example: adding an event on post processing generated assets.
 * ```
 * $listener = new UglifyjsListener();
 * $plugin_api->getConfig()->getEventDispatcher()->addListener(
 *     AssetEvents::POST_PROCESS,
 *     [$listener, 'onPostProcess']
 * );
 * ```
 *
 * Example: adding a processor. A processes how a source file can be converted into a target file.
 * ```
 * $plugin_api->addProcessor(new OptimizeJpgProcessor('jpg'));
 * ```
 */
interface PluginInterface
{
    public function activate(PluginApi $plugin_api): void;
}

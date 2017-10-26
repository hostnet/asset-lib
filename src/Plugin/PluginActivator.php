<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Plugin;

class PluginActivator
{
    /**
     * @var PluginApi
     */
    private $plugin_api;

    private $are_plugins_activated = false;

    public function __construct(PluginApi $plugin_api)
    {
        $this->plugin_api = $plugin_api;
    }

    public function ensurePluginsAreActivated(): void
    {
        if ($this->are_plugins_activated) {
            return;
        }

        foreach ($this->plugin_api->getConfig()->getPlugins() as $plugin) {
            $plugin->activate($this->plugin_api);
        }

        $this->are_plugins_activated = true;
    }
}

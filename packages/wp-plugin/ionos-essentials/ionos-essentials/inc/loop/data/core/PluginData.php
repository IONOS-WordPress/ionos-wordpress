<?php

namespace ionos\essentials\loop\data\core;

use ionos\essentials\loop\data\DataProvider;
use function ionos\essentials\loop\get_plugin_slug;
use function ionos\essentials\loop\normalize_version_string;

/**
 * Plugin Data Provider.
 */
class PluginData extends DataProvider
{
  /**
   * Collects all the statistical data for Plugins.
   *
   * @return array
   */
  protected function collect_data()
  {
    /** WordPress Theme Administration API */
    require_once ABSPATH . 'wp-admin/includes/plugin.php';

    wp_update_plugins();
    $plugin_updates = get_site_transient('update_plugins');
    $plugins        = get_plugins();
    $auto_updates   = get_option('auto_update_plugins', []);

    $parsed_plugins = [];

    foreach ($plugins as $file => $plugin) {
      $parsed_plugins[] = [
        'plugin_slug'  => get_plugin_slug($file),
        'version'      => normalize_version_string($plugin['Version'], true),
        'active'       => is_plugin_active($file),
        'auto_update'  => in_array($file, $auto_updates, true),
        'requires_php' => normalize_version_string($plugin['RequiresPHP']),
        'requires_wp'  => normalize_version_string($plugin['RequiresWP']),
      ];
    }

    return $parsed_plugins;
  }
}

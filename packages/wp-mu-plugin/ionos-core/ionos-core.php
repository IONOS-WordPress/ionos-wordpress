<?php

/**
 * Plugin Name:       Ionos Core
 * Description:       Core functionality for IONOS WordPress projects.
 * Plugin URI:        https://github.com/IONOS-WordPress/ionos-wordpress/tree/main/packages/wp-mu-plugin/ionos-core
 * Requires at least: 6.0
 * Version:           0.5.0
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /ionos-core/languages
 * Text Domain:       ionos-core
 */

namespace ionos\ionos_core;

const PLUGIN_FILE = __FILE__;
const PLUGIN_DIR  = __DIR__;

defined('ABSPATH') || exit();

/**
 * Check if a plugin is active, including custom plugins loaded via ionos stretch extra
 *
 * This function should always be used in favor of WordPress function is_plugin_active
 * to take custom loaded plugins from stretch-extra into account
 */
function _is_plugin_active(string $plugin): bool
{
  if (function_exists('\ionos\stretch_extra\secondary_plugin_dir\is_custom_plugin_active')) {
    $_plugin   = 'plugins/' . $plugin;
    $is_active = \ionos\stretch_extra\secondary_plugin_dir\is_custom_plugin_active($_plugin);
    if ($is_active) {
      return $is_active;
    }
  }

  if (! function_exists('is_plugin_active')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
  }
  return is_plugin_active($plugin);
}

require_once __DIR__ . '/ionos-core/update/index.php';
require_once __DIR__ . '/ionos-core/marketplace/index.php';
require_once __DIR__ . '/ionos-core/loop/index.php';
require_once __DIR__ . '/ionos-core/jetpack-flow/index.php';
require_once __DIR__ . '/ionos-core/login/index.php';

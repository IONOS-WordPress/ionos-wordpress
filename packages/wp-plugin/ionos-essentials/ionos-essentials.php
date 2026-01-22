<?php

/**
 * Plugin Name:       Essentials
 * Description:       The Essentials plugin provides hosting specific functionality.
 * Requires at least: 6.6
 * Requires Plugins:
 * Requires PHP:      8.3
 * Version:           1.4.4
 * Update URI:        https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Flatest/ionos-essentials-info.json
 * Plugin URI:        https://github.com/IONOS-WordPress/ionos-wordpress/tree/main/packages/wp-plugin/ionos-essentials
 * License:           GPL-2.0-or-later
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /ionos-essentials/languages
 * Text Domain:       ionos-essentials
 */

namespace ionos\essentials;

use ionos\essentials\wpscan\WPScan;

const PLUGIN_FILE = __FILE__;
const PLUGIN_DIR  = __DIR__;

defined('ABSPATH') || exit();

\add_action(
  'init',
  function () {
    if (__DIR__ === WPMU_PLUGIN_DIR) {
      \load_muplugin_textdomain(domain: 'ionos-essentials', mu_plugin_rel_path: 'ionos-essentials/languages/');
    } else {
      \load_plugin_textdomain(
        domain: 'ionos-essentials',
        plugin_rel_path: 'ionos-essentials/ionos-essentials/languages/'
      );
    }
  }
);

\add_action('admin_enqueue_scripts', function ($hook): void {
  // enqueue maintenance mode scripts
  $maintenance_mode_assets = require_once __DIR__ . '/ionos-essentials/build/maintenance_mode/index.asset.php';
  \wp_enqueue_script(
    'ionos-essentials-maintenance-mode',
    plugins_url('/ionos-essentials/build/maintenance_mode/index.js', __FILE__),
    $maintenance_mode_assets['dependencies'],
    $maintenance_mode_assets['version'],
    true
  );

  // enqueue security scripts
  $security_assets = require_once __DIR__ . '/ionos-essentials/build/security/index.asset.php';
  \wp_enqueue_script(
    'ionos-essentials-security',
    \plugins_url('/ionos-essentials/build/security/index.js', __FILE__),
    $security_assets['dependencies'],
    $security_assets['version'],
    true,
  );

  // enqueue wpscan scripts
  $token   = \get_option('ionos_security_wpscan_token', '');
  $scripts = empty($token) ? [] : ['plugin-install', 'theme-install', 'theme-overview'];
  foreach ($scripts as $name) {

    $asset_path = __DIR__ . "/ionos-essentials/build/wpscan/{$name}-index.asset.php";
    $script_url = \plugins_url("/ionos-essentials/build/wpscan/{$name}-index.js", __FILE__);

    if (! file_exists($asset_path)) {
      continue;
    }

    $asset = require_once $asset_path;

    \wp_register_script("ionos-essentials-{$name}", $script_url, $asset['dependencies'], $asset['version'], true);

    \wp_set_script_translations(
      "ionos-essentials-{$name}",
      'ionos-essentials',
      PLUGIN_DIR . '/ionos-essentials/languages'
    );
  }

  // enqueue dashboard scripts
  if (ADMIN_PAGE_HOOK !== $hook) {
    return;
  }
  $dashboard_assets = require_once __DIR__ . '/ionos-essentials/build/dashboard/index.asset.php';
  \wp_enqueue_script(
    'ionos-essentials-dashboard',
    \plugins_url('/ionos-essentials/build/dashboard/index.js', __FILE__),
    $dashboard_assets['dependencies'],
    $dashboard_assets['version'],
    true,
  );

  \wp_set_script_translations(
    'ionos-essentials-dashboard',
    'ionos-essentials',
    PLUGIN_DIR . '/ionos-essentials/languages'
  );
});

if (($_GET['ionos-highlight'] ?? '') === 'chatbot') {
  \add_action('wp_enqueue_scripts', function () {
    if (! is_user_logged_in()) {
      return;
    }

    $assets_file = __DIR__ . '/ionos-essentials/build/ai_agent/index.asset.php';
    if (! file_exists($assets_file)) {
      return;
    }

    $assets = require_once $assets_file;

    \wp_enqueue_script(
      'ionos-essentials-ai-agent',
      \plugins_url('ionos-essentials/build/ai_agent/index.js', __FILE__),
      $assets['dependencies'],
      $assets['version'],
      true
    );
  });
}

require_once __DIR__ . '/ionos-essentials/inc/class-tenant.php';
require_once __DIR__ . '/ionos-essentials/inc/tenants/index.php';
require_once __DIR__ . '/ionos-essentials/inc/update/index.php';

// soc plugin components
require_once __DIR__ . '/ionos-essentials/inc/migration/index.php';

// features
require_once __DIR__ . '/ionos-essentials/inc/switch-page/index.php';
require_once __DIR__ . '/ionos-essentials/inc/dashboard/index.php';
require_once __DIR__ . '/ionos-essentials/inc/descriptify/index.php';
require_once __DIR__ . '/ionos-essentials/inc/jetpack-flow/index.php';
require_once __DIR__ . '/ionos-essentials/inc/login/index.php';
require_once __DIR__ . '/ionos-essentials/inc/security/index.php';
require_once __DIR__ . '/ionos-essentials/inc/maintenance_mode/index.php';
require_once __DIR__ . '/ionos-essentials/inc/wpscan/index.php';
require_once __DIR__ . '/ionos-essentials/inc/loop/index.php';
require_once __DIR__ . '/ionos-essentials/inc/adminbar/index.php';

require_once __DIR__ . '/ionos-essentials/inc/extendify/index.php';
require_once __DIR__ . '/ionos-essentials/inc/mcp/index.php';

// soc plugin components
require_once __DIR__ . '/ionos-essentials/inc/migration/index.php';

if (! defined('IONOS_IS_STRETCH')) {
  define('IONOS_IS_STRETCH', str_starts_with(getcwd(), '/home/www/public'));
}

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

// TODO: evaluate for other tenants than IONOS

// \add_filter(
//   'gettext_ionos-essentials',
//   function ($text) {
//     if ( ! str_contains($text, 'IONOS')) {
//       return $text;
//     }
//     $brand_name = \get_option('ionos_brand_name', 'IONOS');
//     // TODO: replace more? menu_brand, non capital, ...
//     return str_replace('IONOS', $brand_name, $text);
//   }
// );

<?php

/**
 * Plugin Name:       Essentials
 * Description:       The essentials plugin provides IONOS hosting specific functionality.
 * Requires at least: 6.6
 * Requires Plugins:
 * Requires PHP:      8.3
 * Version:           1.3.3
 * Update URI:        https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Flatest/ionos-essentials-info.json
 * Plugin URI:        https://github.com/IONOS-WordPress/ionos-wordpress/tree/main/packages/wp-plugin/ionos-essentials
 * License:           GPL-2.0-or-later
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /ionos-essentials/languages
 * Text Domain:       ionos-essentials
 */

namespace ionos\essentials;

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

\add_action('admin_enqueue_scripts', function (): void {

  // enqueue dashboard scripts
  $dashboard_assets = require_once __DIR__ . '/ionos-essentials/build/dashboard/index.asset.php';
  \wp_enqueue_script(
    'ionos-essentials-dashboard',
    \plugins_url('/ionos-essentials/build/dashboard/index.js', __FILE__),
    $dashboard_assets['dependencies'],
    $dashboard_assets['version'],
    [
      'in_footer' => true,
    ],
  );

  wp_set_script_translations('ionos-essentials-dashboard', 'ionos-essentials', PLUGIN_DIR . '/ionos-essentials/languages');

  // enqueue maintenance mode scripts
  $maintenace_mode_assets = require_once __DIR__ . '/ionos-essentials/build/maintenance_mode/index.asset.php';
  \wp_enqueue_script(
    'ionos-essentials-maintenance-mode',
    \plugins_url('/ionos-essentials/build/maintenance_mode/index.js', __FILE__),
    $maintenace_mode_assets['dependencies'],
    $maintenace_mode_assets['version'],
    [
      'in_footer' => true,
    ],
  );

  // enqueue security scripts
  $security_assets = require_once __DIR__ . '/ionos-essentials/build/security/index.asset.php';
  \wp_enqueue_script(
    'ionos-essentials-security',
    \plugins_url('/ionos-essentials/build/security/index.js', __FILE__),
    $security_assets['dependencies'],
    $security_assets['version'],
    [
      'in_footer' => true,
    ],
  );


  // enqueue wpscan scripts
  $wpscan_assets = include_once __DIR__ . '/ionos-essentials/build/wpscan/index.asset.php';
  \wp_enqueue_script(
    'ionos-essentials-wpscan',
    \plugins_url('/ionos-essentials/build/wpscan/index.js', __FILE__),
    $wpscan_assets['dependencies'],
    $wpscan_assets['version'],
    [
      'in_footer' => true,
    ],
  );

  wp_set_script_translations('ionos-essentials-wpscan', 'ionos-essentials', PLUGIN_DIR . '/ionos-essentials/languages');

});

require_once __DIR__ . '/ionos-essentials/inc/class-tenant.php';
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
if ('local' === \wp_get_environment_type()) {
  require_once __DIR__ . '/ionos-essentials/inc/loop/index.php';
}
require_once __DIR__ . '/ionos-essentials/inc/extendify/index.php';

// soc plugin components
require_once __DIR__ . '/ionos-essentials/inc/migration/index.php';

function is_stretch(): bool
{
  return str_starts_with(getcwd(), '/home/www/public');
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

<?php

/**
 * Plugin Name:       Essentials
 * Description:       The essentials plugin provides IONOS hosting specific functionality.
 * Requires at least: 6.6
 * Requires Plugins:
 * Requires PHP:      8.3
 * Version:           1.0.9
 * Update URI:        https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Flatest/ionos-essentials-info.json
 * Plugin URI:        https://github.com/IONOS-WordPress/ionos-wordpress/tree/main/packages/wp-plugin/essentials
 * License:           GPL-2.0-or-later
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /languages
 * Text Domain:       ionos-essentials
 */

namespace ionos\essentials;

const PLUGIN_FILE = __FILE__;
const PLUGIN_DIR  = __DIR__;

defined('ABSPATH') || exit();

\add_action(
  'init',
  fn () => \load_plugin_textdomain(domain: 'ionos-essentials', plugin_rel_path: basename(__DIR__) . '/languages/')
);

require_once __DIR__ . '/inc/update/index.php';

// features
require_once __DIR__ . '/inc/switch-page/index.php';
require_once __DIR__ . '/inc/dashboard/index.php';
require_once __DIR__ . '/inc/descriptify/index.php';
require_once __DIR__ . '/inc/jetpack-flow/index.php';
require_once __DIR__ . '/inc/login/index.php';

// soc plugin components
require_once __DIR__ . '/inc/migration/index.php';

function is_stretch()
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

\add_action('rest_api_init', function () {
  \register_rest_route(
    'ionos/essentials/option',
    '/set',
    [
      'methods'             => 'POST',
      'permission_callback' => fn () => 0 !== \get_current_user_id(),
      'callback'            => function () {
        $params      = json_decode(file_get_contents('php://input'), true);
        $option_name = $params['option_name'] ?? '';
        $key         = $params['key']         ?? '';
        $value       = $params['value']       ?? '';
        return rest_ensure_response(new \WP_REST_Response([
          'status' => $key,
          'value'  => $value,
        ], 200));
      },
    ]
  );
}, 1);

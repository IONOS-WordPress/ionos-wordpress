<?php

namespace ionos_wordpress\test_mu_plugin\feature_2;

function feature_2(): void {
  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
  error_log('hello from ionos_wordpress\test_mu_plugin\feature_2');
}

feature_2();

// \add_action('admin_enqueue_scripts', function (): void {
//   $assets = include_once __DIR__ . '/build/index.asset.php';
//   \wp_enqueue_script(
//     handle: 'test-plugin-index',
//     src: \plugins_url('/build/index.js', __FILE__),
//     deps: $assets['dependencies'],
//     ver: $assets['version'],
//     args: [
//       'in_footer' => true,
//     ],
//   );
//   \wp_set_script_translations('test-plugin-index', 'test-mu-plugin', \plugin_dir_path(__FILE__) . 'languages');
// });

<?php

namespace ionos_wordpress\test_plugin\feature_2\frontend;

function hello(): void
{
  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
  error_log('hello from packages/wp-plugin/test-plugin/src/feature-2/frontend/feature-2.php');
}

hello();

\add_action('admin_enqueue_scripts', function (): void {
  $assets = require_once __DIR__ . '/feature-2-index.asset.php';
  \wp_enqueue_script(
    handle: 'test-plugin-feature-2-frontend-index',
    src: \plugins_url('/feature-2-index.js', __FILE__),
    deps: $assets['dependencies'],
    ver: $assets['version'],
    args: [
      'in_footer' => true,
    ],
  );

  \wp_set_script_translations(
    'test-plugin-feature-2-frontend-index',
    'test-plugin',
    \plugin_dir_path(__FILE__) . 'languages'
  );
});

require_once __DIR__ . '/feature-2-special.php';

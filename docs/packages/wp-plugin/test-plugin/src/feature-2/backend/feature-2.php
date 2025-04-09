<?php

namespace ionos\test_plugin\feature_2\backend;

function hello(): void
{
  error_log('hello from packages/wp-plugin/test-plugin/src/feature-2/backend/feature-2.php');
}

hello();

\add_action('admin_enqueue_scripts', function (): void {
  $assets = require_once __DIR__ . '/index.asset.php';
  \wp_enqueue_script(
    handle: 'test-plugin-feature-2-backend-index',
    src: \plugins_url('/index.js', __FILE__),
    deps: $assets['dependencies'],
    ver: $assets['version'],
    args: [
      'in_footer' => true,
    ],
  );

  \wp_set_script_translations(
    'test-plugin-feature-2-backend-index',
    'test-plugin',
    \plugin_dir_path(__FILE__) . 'languages'
  );
});

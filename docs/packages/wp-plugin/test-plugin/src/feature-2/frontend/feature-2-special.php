<?php

namespace ionos\test_plugin\feature_2\frontend;

function hello2(): void
{
  error_log('hello from packages/wp-plugin/test-plugin/src/feature-2/frontend/feature-2-special.php');
}

hello2();

\add_action('admin_enqueue_scripts', function (): void {
  $assets = require_once __DIR__ . '/feature-2-special-index.asset.php';
  \wp_enqueue_script(
    handle: 'test-plugin-feature-2-frontend-special-index',
    src: \plugins_url('/feature-2-special-index.js', __FILE__),
    deps: $assets['dependencies'],
    ver: $assets['version'],
    args: [
      'in_footer' => true,
    ],
  );

  \wp_set_script_translations(
    'test-plugin-feature-2-frontend-special-index',
    'test-plugin',
    \plugin_dir_path(__FILE__) . 'languages'
  );
});

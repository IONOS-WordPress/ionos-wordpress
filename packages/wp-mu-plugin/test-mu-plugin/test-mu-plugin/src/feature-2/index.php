<?php

namespace ionos\test_mu_plugin\feature_2;

use const ionos\test_mu_plugin\PLUGIN_FILE;

function feature_2(): void
{
  error_log('hello from ionos\test_mu_plugin\feature_2');
}

feature_2();

\add_action('admin_enqueue_scripts', function (): void {
  $assets = include_once __DIR__ . '/index.asset.php';
  \wp_enqueue_script(
    handle: 'test-mu-plugin-feature-2-index',
    src: \plugins_url('test-mu-plugin/build/feature-2/index.js', PLUGIN_FILE),
    deps: $assets['dependencies'],
    ver: $assets['version'],
    args: [
      'in_footer' => true,
    ],
  );
  \wp_set_script_translations('test-mu-plugin-feature-2-index', 'test-mu-plugin', PLUGIN_FILE . '/languages');
});

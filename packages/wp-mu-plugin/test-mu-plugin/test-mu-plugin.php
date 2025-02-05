<?php

/**
 * Plugin Name:       ionos-wordpress/test-mu-plugin
 * Description:       a test mu plugin
 * Requires at least: 6.6
 * Requires Plugins:
 * Requires PHP:      8.3
 * Version:           0.0.1
 * License:           GPL-2.0-or-later
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /languages
 */

namespace ionos_wordpress\test_mu_plugin;

defined('ABSPATH') || exit();

const PLUGIN_FILE = __FILE__;

\add_action('init', function (): void {
  \load_muplugin_textdomain(domain: 'test-mu-plugin', mu_plugin_rel_path: basename(__DIR__) . '/test-mu-plugin/languages/');

  $translated_text = \__('hello.world', 'test-mu-plugin');
  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
  error_log($translated_text);
});

\add_action('admin_enqueue_scripts', function (): void {
  $assets = include_once __DIR__ . '/test-mu-plugin/build/index.asset.php';
  \wp_enqueue_script(
    handle: 'test-mu-plugin-index',
    src: \plugins_url('/test-mu-plugin/build/index.js', __FILE__),
    deps: $assets['dependencies'],
    ver: $assets['version'],
    args: [
      'in_footer' => true,
    ],
  );
  \wp_set_script_translations(handle : 'test-test-mu-plugin-index', domain : 'test-mu-plugin', path : basename(__DIR__) . '/test-mu-plugin/languages');
});

require_once __DIR__ . '/test-mu-plugin/inc/feature-1.php';
require_once __DIR__ . '/test-mu-plugin/build/feature-2/index.php';

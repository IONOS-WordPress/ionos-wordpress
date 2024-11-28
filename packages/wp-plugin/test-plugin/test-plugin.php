<?php
/**
 * Plugin Name:       ionos-wordpress/test-plugin
 * Description:       a test plugin
 * Requires at least: 6.6
 * Requires Plugins:
 * Requires PHP:      8.3
 * Version:           0.0.1
 * License:           GPL-2.0-or-later
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /languages
 */

namespace ionos_wordpress\essentials;

defined('ABSPATH') || exit();

\add_action('init', function (): void {
  $foo = 'bar';

  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
  error_log("foo={$foo}");
});

\add_action('init', function (): void {
  \load_plugin_textdomain(domain: 'test-plugin', plugin_rel_path: basename(__DIR__) . '/languages/');

  $translated_text = \__('hello.world', 'test-plugin');
  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
  error_log($translated_text);
});

\add_action('admin_enqueue_scripts', function (): void {
  $assets = include_once __DIR__ . '/build/index.asset.php';
  \wp_enqueue_script(
    handle: 'test-plugin-index',
    src: \plugins_url('/build/index.js', __FILE__),
    deps: $assets['dependencies'],
    ver: $assets['version'],
    args: [
      'in_footer' => true,
    ],
  );

  \wp_set_script_translations('test-plugin-index', 'test-plugin', \plugin_dir_path(__FILE__) . 'languages');
});

<?php

/**
 * Plugin Name:       ionos-wordpress/test-plugin
 * Description:       a test plugin
 * Requires at least: 6.6
 * Requires Plugins:
 * Requires PHP:      8.3
 * Version:           0.0.1
 * Update URI:        https://api.github.com/repos/IONOS-WordPress/ionos-wordpress/releases
 * Plugin URI:        https://github.com/IONOS-WordPress/ionos-wordpress/tree/main/packages/wp-plugin/test-plugin
 * License:           GPL-2.0-or-later
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /languages
 */

namespace ionos\test_plugin;

defined('ABSPATH') || exit();

/* this is just demo code how to use enums and to see how rector transforms it */
enum ModeParam: string
{
  case LOCALE = 'local';
  case REMOTE = 'remote';
}

function dummy_function_using_mode_param(ModeParam $mode_param, int $count): void
{
  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
  error_log("mode_param={$mode_param}, count={$count}");
}

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

require_once __DIR__ . '/build/feature-1/feature-1.php';
require_once __DIR__ . '/build/feature-2/feature-2.php';

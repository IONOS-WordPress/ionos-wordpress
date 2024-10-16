<?php
/**
 * Plugin Name:       ionos-wordpress/test-plugin
 * Description:       a test plugin
 * Requires at least: 6.6
 * Requires Plugins:
 * Requires PHP:      8.3
 * Version:           0.0.1
 * Author:            lars gersmann <lars.gersmann@ionos.com>
 * License:           @TODO: add license
 * License URI:       @TODO: add license url
 * Text Domain:       ionos-wordpress/test-plugin
 */

namespace ionos_wordpress\essentials;

defined( 'ABSPATH' ) || exit;

\add_action('init', function() : void {
  $foo = "bar";

  error_log("foo=$foo");
});

<?php
/**
 * Plugin Name:       ionos-wordpress/essentials
 * Description:       The essentials plugins hosts IONOS hosting specific functionality.
 * Requires at least: 6.6
 * Requires Plugins:
 * Requires PHP:      8.3
 * Version:           0.0.1
 * Author:            lars gersmann <lars.gersmann@ionos.com>
 * License:           @TODO: add license
 * License URI:       @TODO: add license url
 * Text Domain:       ionos-wordpress/essentials
 */

namespace devcontainer_boilerplate\plugin_a;

defined( 'ABSPATH' ) || exit;

\add_action('init', function() : void {
  $foo = "bar";

  error_log("foo=$foo");
});

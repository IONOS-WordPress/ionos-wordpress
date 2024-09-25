<?php
/**
 * Plugin Name:       ionos-wordpress/essentials
 * Description:       a simple dummy plugin called essentials
 * Requires at least: 6.6
 * Requires Plugins:
 * Requires PHP:      8.3
 * Version:           1.0.0
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

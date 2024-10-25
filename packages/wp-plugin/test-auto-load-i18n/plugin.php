<?php
/**
 * Plugin Name:       test-auto-load-i18n
 * Description:       tests auto loading of i18n files
 * Requires at least: 6.6
 * Requires Plugins:
 * Requires PHP:      8.2
 * Version:           0.0.1
 * Author:            lars gersmann <lars.gersmann@ionos.com>
 * License:           @TODO: add license
 * License URI:       @TODO: add license url
 * Text Domain:       test-auto-load-i18n
 * Domain Path:       /languages
 */

namespace ionos_wordpress\test_auto_load_i18n;

defined( 'ABSPATH' ) || exit;

\add_action('init', function() : void {
  error_log(\__('hello.world', 'test-auto-load-i18n'));
});

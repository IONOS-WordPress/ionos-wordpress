<?php
/**
 * Plugin Name:       ionos-wordpress/test-auto-load-i18n
 * Description:       tests auto loading of i18n files
 * Requires at least: 6.6
 * Requires Plugins:
 * Requires PHP:      8.2
 * Version:           0.0.1
 * License:           GPL-2.0-or-later
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /languages
 */

namespace ionos_wordpress\test_auto_load_i18n;

defined( 'ABSPATH' ) || exit;

\add_action('plugins_loaded', function() : void {
  \load_plugin_textdomain(
    domain : 'test-auto-load-i18n',
    plugin_rel_path: basename( __DIR__ ) . '/languages/'
  );

  $translatedText = \__('hello.world', 'test-auto-load-i18n');
  error_log($translatedText);
});

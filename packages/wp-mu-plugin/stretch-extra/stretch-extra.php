<?php

/**
 * Plugin Name:       stretch-extra
 * Description:       stretch-extra acts as a wp-env shim to include the php code targeting /opt/WordPress/extra/index.php
 * Requires at least: 6.6
 * Requires Plugins:
 * Requires PHP:      8.3
 * Version:           1.0.0
 * Plugin URI:        https://github.com/IONOS-WordPress/ionos-wordpress/tree/main/packages/wp-mu-plugin/stretch-extra
 * License:           GPL-2.0-or-later
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /stretch-extra/languages
 * Text Domain:       stretch-extra
 */

namespace ionos\stretch_extra;

defined('ABSPATH') || exit();

// Set SFS server variable to fake stretch-extra context
if (! defined('IONOS_IS_STRETCH_SFS')  && defined('IONOS_IS_STRETCH') && IONOS_IS_STRETCH) {
  define('IONOS_IS_STRETCH_SFS', array_key_exists('SFS', $_SERVER));
}
if (! IONOS_IS_STRETCH_SFS) {
  $_SERVER['SFS'] = 'stretch-extra';
}

// abort if called from WP-CLI to avoid issues with command line scripts
// (need to prevent execution while wp was loaded by wp-cli)
if (defined('WP_CLI')) {
  return;
}

const EXTRA_ENTRYPOINT = __DIR__ . '/stretch-extra/index.php';
require_once EXTRA_ENTRYPOINT;

/*
  rest of the file is just sugar for logging errors during inclusion
 */

// Check for the error PHP might have registered
$_error = error_get_last();

$wp_debug_log_enabled = defined('WP_DEBUG_LOG') && WP_DEBUG_LOG;

// Fix admin menu position if there was a PHP error during inclusion
$wp_debug_log_enabled && \add_action('admin_head', function () {
  echo <<<HTML
    <style type="text/css">
      .php-error #adminmenuback {
        position: inherit;
      }

      .php-error #adminmenuback,
      .php-error #adminmenuwrap {
        margin-top: inherit;
      }
    </style>
  HTML;
});

// If there was no error and WP_DEBUG_LOG is enabled, log successful inclusion
if (! $_error) {
  $wp_debug_log_enabled && error_log('Successfully included script: ' . EXTRA_ENTRYPOINT);
}

// Check if the last error was a "failed opening required" warning
if (strpos($_error['message'] ?? '', 'failed opening required') !== false) {
  error_log(sprintf("Failed to include script '%s'. File not found or inaccessible.", EXTRA_ENTRYPOINT));
}

// Check if the last error was not an E_WARNING (E_PARSE is a fatal error, etc.)
if ($_error['type'] ?? '' === E_WARNING) {
  error_log(sprintf("An issue occurred while including '%s'. Message: {$_error['message']}", EXTRA_ENTRYPOINT));
}

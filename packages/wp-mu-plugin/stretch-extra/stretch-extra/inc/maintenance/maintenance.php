<?php

/**
 * Maintenance Mode - Request enforcement handler
 *
 * This file is symlinked to wp-content/maintenance-mode.php on
 * maintenance mode activation and loaded by the stretch-extra mu-plugin via
 * include_once. It registers an action on muplugins_loaded — the earliest
 * available WordPress hook — to serve a 503 maintenance response before plugins
 * and themes are loaded.
 *
 * The handler is a no-op in WP-CLI context (php_sapi_name() !== 'fpm-fcgi') so
 * CLI commands are never blocked by their own maintenance mode.
 */

namespace ionos\stretch_extra\maintenance;

use const ionos\stretch_extra\MAINTENANCE_HANDLER_LINK_PATH;

defined('ABSPATH') || exit();

// Exit early if running in WP-CLI context to avoid blocking CLI commands with maintenance mode.
if (PHP_SAPI === 'cli') {
  return;
}

/**
 * Enforce maintenance mode on incoming web requests.
 *
 * Reads the sentinel file, checks the timestamp against MAINTENANCE_EXPIRY_SECONDS,
 * applies the enable_maintenance_mode filter, then either serves the
 * wp-content/maintenance.php drop-in or calls wp_die() with a 503 + Retry-After
 * response.
 */
\add_action(
  'muplugins_loaded',
  function (): void {
    $status = _get_maintenance_mode_status();

    if (! $status['active']) {
      return;
    }

    /** @var int $upgrading */
    $upgrading = $status['timestamp'];

    // Respect the enable_maintenance_mode filter (mirrors WordPress core pattern)
    if (! \apply_filters('enable_maintenance_mode', true, $upgrading)) {
      return;
    }

    // // Serve custom maintenance.php drop-in if present
    // if (file_exists(MAINTENANCE_HANDLER_LINK_PATH)) {
    //   require_once MAINTENANCE_HANDLER_LINK_PATH;
    //   exit;
    // }

    // Default fallback: 503 with Retry-After header
    \wp_die(
      message: \__('Briefly unavailable for scheduled maintenance. Check back in a minute.'),
      title: \__('Maintenance'),
      args: [
        'response'           => 503,
        'additional_headers' => [
          'Retry-After' => (string) MAINTENANCE_EXPIRY_SECONDS,
        ],
      ],
    );
  }
);

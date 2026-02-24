<?php

/**
 * Maintenance Mode - Core activation and deactivation logic
 *
 * Manages the stretch-extra maintenance mode by creating/removing a handler symlink
 * in wp-content/ and writing/deleting a sentinel file that stores the activation
 * timestamp. Maintenance mode auto-expires after MAINTENANCE_EXPIRY_SECONDS even
 * without an explicit deactivate call.
 *
 * Activation creates:
 *   - Symlink: WP_CONTENT_DIR/maintenance-mode.php → handler file
 *   - Sentinel: WP_CONTENT_DIR/.stretch-extra-maintenance (PHP file with $upgrading timestamp)
 *
 * Deactivation removes both.
 */

namespace ionos\stretch_extra\maintenance;

defined('ABSPATH') || exit();

use const ionos\stretch_extra\IONOS_CUSTOM_DIR;
use const ionos\stretch_extra\MAINTENANCE_HANDLER_LINK_PATH;

/** Path to the sentinel file that stores the activation timestamp. */
const MAINTENANCE_SENTINEL_PATH = WP_CONTENT_DIR . '/.stretch-extra-maintenance';

/** Path to the handler file that the symlink points to. */
const MAINTENANCE_HANDLER_SOURCE = IONOS_CUSTOM_DIR . '/inc/maintenance/maintenance.php';

/** Number of seconds after which maintenance mode auto-expires. */
const MAINTENANCE_EXPIRY_SECONDS = 600; // 10 minutes

/**
 * Activate maintenance mode.
 *
 * Creates the handler symlink in wp-content/ (if absent) and writes the sentinel
 * file with the current Unix timestamp. Re-activating while already active
 * resets the expiry timer.
 *
 * @return bool True on success, false on failure.
 */
function activate(): bool
{
  if (! file_exists(MAINTENANCE_HANDLER_LINK_PATH)) {
    if (! symlink(MAINTENANCE_HANDLER_SOURCE, MAINTENANCE_HANDLER_LINK_PATH)) {
      return false;
    }
  }

  $content = sprintf("<?php \$upgrading = %d; ?>\n", time());

  $retval = file_put_contents(MAINTENANCE_SENTINEL_PATH, $content) !== false;

  error_log('Maintenance mode activated. Sentinel file updated with timestamp: ' . time());

  return $retval;
}

/**
 * Deactivate maintenance mode.
 *
 * Removes the handler symlink and deletes the sentinel file.
 * Safe to call when maintenance mode is already inactive.
 *
 * @return bool True on success (including no-op), false if any removal failed.
 */
function deactivate(): bool
{
  $success = true;

  if (is_link(MAINTENANCE_HANDLER_LINK_PATH) || file_exists(MAINTENANCE_HANDLER_LINK_PATH)) {
    $success = unlink(MAINTENANCE_HANDLER_LINK_PATH) && $success;
  }

  if (file_exists(MAINTENANCE_SENTINEL_PATH)) {
    $success = unlink(MAINTENANCE_SENTINEL_PATH) && $success;
  }

  error_log('Maintenance mode deactivated. Sentinel file and handler link removed if they existed.');

  return $success;
}

/**
 * Return the current maintenance mode status.
 *
 * @return array{active: bool, timestamp: int|null, expired: bool}
 */
function _get_maintenance_mode_status(): array
{
  if (! file_exists(MAINTENANCE_SENTINEL_PATH)) {
    return [
      'active'    => false,
      'timestamp' => null,
      'expired'   => false,
    ];
  }

  $upgrading = null;
  include MAINTENANCE_SENTINEL_PATH; // sets $upgrading

  if (! is_int($upgrading)) {
    return [
      'active'    => false,
      'timestamp' => null,
      'expired'   => false,
    ];
  }

  $expired        = (time() - $upgrading) >= MAINTENANCE_EXPIRY_SECONDS;
  $symlink_active = is_link(MAINTENANCE_HANDLER_LINK_PATH) || file_exists(MAINTENANCE_HANDLER_LINK_PATH);

  return [
    'active'    => $symlink_active && ! $expired,
    'timestamp' => $upgrading,
    'expired'   => $expired,
  ];
}

if (defined('WP_CLI')) {
  require_once __DIR__ . '/wp-cli.php';
}

/**
 * deactivate() is called on the upgrader_process_complete hook after plugin/theme/core updates.
 * This ensures that maintenance mode is turned off after updates complete, even if the process
 * was initiated outside of our CLI command (e.g. via the WP admin or another tool).
 */
\add_action(
  hook_name: 'upgrader_process_complete',
  callback: function ($upgrader, $options) {
    // $options['type'] will be 'plugin', 'theme', 'core', or 'translation'
    // $options['action'] will be 'update' or 'install'

    deactivate();
  },
  accepted_args: 2,
);

/**
 * activate() is called before an update or installation begins.
 * This ensures that maintenance mode is turned on during the update process.
 * Note: This will activate maintenance mode for the entire duration of the update process,
 * which may be longer than expected. The upgrader_process_complete hook will ensure it is
 * deactivated afterward.
 */
\add_filter(
  'upgrader_pre_install',
  function ($response, $hook_extra) {
    // $hook_extra['type'] will be 'plugin', 'theme', 'core', or 'translation'
    // $hook_extra['action'] will be 'update' or 'install'
    activate();
  },
  accepted_args: 2,
);

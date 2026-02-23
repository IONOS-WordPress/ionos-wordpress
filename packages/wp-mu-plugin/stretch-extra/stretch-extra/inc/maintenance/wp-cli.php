<?php

/**
 * Maintenance Mode - WP-CLI commands
 *
 * Provides `wp maintenance-mode activate`, `wp maintenance-mode deactivate`,
 * `wp maintenance-mode status`, and `wp maintenance-mode is-active` commands
 * for controlling the stretch-extra maintenance mode.
 *
 * Note: This overrides the built-in WP-CLI maintenance-mode command since the
 * target hosting platform has a read-only WordPress root directory and cannot use
 * the core .maintenance file mechanism. stretch-extra uses a symlink in wp-content/
 * and a sentinel file wp-content/.stretch-extra-maintenance instead.
 */

namespace ionos\stretch_extra\maintenance;

defined('ABSPATH') || exit();

/**
 * Manages WordPress maintenance mode for hosting platforms with a read-only
 * WordPress root directory.
 */
class Maintenance_Mode_Command {
  /**
   * Activate maintenance mode.
   *
   * Creates the handler symlink and sentinel file. Re-activating while already
   * active resets the expiry timer.
   *
   * ## EXAMPLES
   *
   *     $ wp maintenance-mode activate
   *     Success: Maintenance mode activated.
   *
   * @subcommand activate
   */
  public function activate(array $args, array $assoc_args): void {
    if (! activate()) {
      \WP_CLI::error('Failed to activate maintenance mode. Check file system permissions.');
    }

    \WP_CLI::success('Maintenance mode activated.');
  }

  /**
   * Deactivate maintenance mode.
   *
   * Removes the handler symlink and sentinel file.
   *
   * ## EXAMPLES
   *
   *     $ wp maintenance-mode deactivate
   *     Success: Maintenance mode deactivated.
   *
   * @subcommand deactivate
   */
  public function deactivate(array $args, array $assoc_args): void {
    if (! deactivate()) {
      \WP_CLI::error('Failed to deactivate maintenance mode. Check file system permissions.');
    }

    \WP_CLI::success('Maintenance mode deactivated.');
  }

  /**
   * Display the current maintenance mode status.
   *
   * ## EXAMPLES
   *
   *     $ wp maintenance-mode status
   *     Success: Maintenance mode is active (activated 42 seconds ago).
   *
   *     $ wp maintenance-mode status
   *     Maintenance mode is inactive.
   *
   *     $ wp maintenance-mode status
   *     Warning: Maintenance mode is expired (activated 720 seconds ago, limit is 600 seconds).
   *
   * @subcommand status
   */
  public function status(array $args, array $assoc_args): void {
    $status = _get_maintenance_mode_status();

    if ($status['timestamp'] === null) {
      \WP_CLI::line('Maintenance mode is inactive.');
      return;
    }

    $age = time() - $status['timestamp'];

    if ($status['expired']) {
      \WP_CLI::warning(sprintf(
        'Maintenance mode is expired (activated %d seconds ago, limit is %d seconds).',
        $age,
        MAINTENANCE_EXPIRY_SECONDS,
      ));
      return;
    }

    if ($status['active']) {
      \WP_CLI::success(sprintf(
        'Maintenance mode is active (activated %d seconds ago).',
        $age,
      ));
      return;
    }

    \WP_CLI::line('Maintenance mode is inactive.');
  }

  /**
   * Check whether maintenance mode is currently active.
   *
   * Exits with code 0 if active, 1 otherwise. Suitable for use in shell
   * conditionals and scripts.
   *
   * ## EXAMPLES
   *
   *     $ wp maintenance-mode is-active && echo "site is down"
   *
   * @subcommand is-active
   */
  public function is_active(): void {
    $status = _get_maintenance_mode_status();

    \WP_CLI::halt($status['active'] ? 0 : 1);
  }
}

\WP_CLI::add_command('maintenance-mode', Maintenance_Mode_Command::class);

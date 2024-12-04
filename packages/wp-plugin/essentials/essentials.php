<?php
/**
 * Plugin Name:       ionos-wordpress/essentials
 * Description:       The essentials plugins hosts IONOS hosting specific functionality.
 * Requires at least: 6.6
 * Requires Plugins:
 * Requires PHP:      8.3
 * Version:           0.0.3
 * License:           GPL-2.0-or-later
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /languages
 */

namespace ionos_wordpress\essentials;

defined('ABSPATH') || exit();

enum Mode: string
{
  case LOCALE = 'local';
  case REMOTE = 'remote';
}

function foo(Mode $mode, int $count): void
{
  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
  error_log("mode={$mode}, count={$count}");
}

\add_action('init', function (): void {
  $foo = 'bar';

  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
  error_log("foo={$foo}");
});

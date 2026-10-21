<?php

/*
  run as separate php process by MuPluginUpgraderTest::test_real_fatal_error_mid_swap_is_rolled_back_on_shutdown().

  installs a package using an upgrader whose move of the new loader dies with a real PHP fatal error.
  usage: php fatal-during-swap.php <abspath> <package dir> <target dir>
 */

[, $abspath, $source, $target] = $argv;

// load only the wordpress core (no plugins), but including the 'shutdown' action registration
define('SHORTINIT', true);
require $abspath . 'wp-load.php';
// SHORTINIT skips these, but the upgrader needs them
require_once ABSPATH . WPINC . '/l10n.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skin.php';
require_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';
require_once dirname(__DIR__, 3) . '/class-mu-plugin-upgrader.php';

class Dying_MU_Plugin_Upgrader extends \ionos\ionos_core\MU_Plugin_Upgrader {
  protected function set_maintenance_mode(bool $enable): void {
    // do not touch the site
  }

  protected function move(string $from, string $to): bool {
    if (str_contains($from, '.ionos-core-staging-') && str_ends_with($to, '/ionos-core.php')) {
      // real fatal error
      undefined_function_that_kills_the_request();
    }
    return parent::move($from, $to);
  }
}

WP_Filesystem();

(new Dying_MU_Plugin_Upgrader())->install($source, $target);

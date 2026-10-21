<?php

namespace ionos\ionos_core;

defined('ABSPATH') || exit();

require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/file.php';

class MU_Plugin_Upgrader extends \WP_Upgrader
{
  public function __construct()
  {
    parent::__construct(new \Automatic_Upgrader_Skin());
  }

  public function upgrade(string $package_url)
  {
    global $wp_filesystem;

    \WP_Filesystem();
    $this->init();

    $package = $this->download_package($package_url);
    if (\is_wp_error($package)) {
      return $package;
    }

    $working_dir = $this->unpack_package($package, true);
    if (\is_wp_error($working_dir)) {
      return $working_dir;
    }

    $result = $this->install($working_dir, WPMU_PLUGIN_DIR);

    $wp_filesystem->delete($working_dir, true);

    return $result;
  }

  /**
   * entries of the package that make up the mu-plugin (loader file and plugin directory)
   */
  protected const PLUGIN_ENTRIES = ['ionos-core', 'ionos-core.php'];

  /**
   * replaces the installed plugin entries by the ones from $source_dir.
   *
   * the new version is staged next to the live plugin first, the live entries are then moved
   * to a backup directory and the staged entries are moved in place (renames only).
   * if anything fails the previous version is restored.
   *
   * @return true|\WP_Error
   */
  public function install(string $source_dir, string $target_dir)
  {
    global $wp_filesystem;

    $source_dir = \trailingslashit($source_dir);
    $target_dir = \trailingslashit($target_dir);

    foreach (static::PLUGIN_ENTRIES as $entry) {
      if (! \file_exists($source_dir . $entry)) {
        return new \WP_Error('ionos_core_update_invalid_package', "Package does not contain '$entry'");
      }
    }

    $suffix      = \uniqid();
    $staging_dir = $target_dir . '.ionos-core-staging-' . $suffix . '/';
    $backup_dir  = $target_dir . '.ionos-core-backup-' . $suffix . '/';

    // stage the new version (the live plugin is untouched until this succeeded)
    if (! $wp_filesystem->mkdir($staging_dir)) {
      return new \WP_Error('ionos_core_update_stage_failed', 'Could not create staging directory');
    }

    $result = \copy_dir($source_dir, $staging_dir);
    if (\is_wp_error($result)) {
      $wp_filesystem->delete($staging_dir, true);
      return $result;
    }

    foreach (static::PLUGIN_ENTRIES as $entry) {
      if (! \file_exists($staging_dir . $entry)) {
        $wp_filesystem->delete($staging_dir, true);
        return new \WP_Error('ionos_core_update_stage_failed', "Staging of '$entry' failed");
      }
    }

    // swap: live -> backup, staging -> live. $backed_up/$installed track what has to be reverted.
    $backed_up = [];
    $installed = [];
    $error     = null;

    if (! $wp_filesystem->mkdir($backup_dir)) {
      $error = 'Could not create backup directory';
    }

    if (! $error) {
      foreach (static::PLUGIN_ENTRIES as $entry) {
        if (\file_exists($target_dir . $entry)) {
          if (! $this->move($target_dir . $entry, $backup_dir . $entry)) {
            $error = "Could not back up '$entry'";
            break;
          }
          $backed_up[] = $entry;
        }
      }
    }

    if (! $error) {
      foreach (static::PLUGIN_ENTRIES as $entry) {
        if (! $this->move($staging_dir . $entry, $target_dir . $entry)) {
          $error = "Could not install '$entry'";
          break;
        }
        $installed[] = $entry;
      }
    }

    if ($error) {
      return $this->rollback($error, $target_dir, $staging_dir, $backup_dir, $backed_up, $installed);
    }

    $wp_filesystem->delete($staging_dir, true);
    $wp_filesystem->delete($backup_dir, true);

    return true;
  }

  /**
   * restores the previous plugin version after a failed swap.
   */
  private function rollback(
    string $error,
    string $target_dir,
    string $staging_dir,
    string $backup_dir,
    array $backed_up,
    array $installed
  ): \WP_Error {
    global $wp_filesystem;

    $restore_failures = [];

    // remove what was already installed from the new version
    foreach ($installed as $entry) {
      if (! $wp_filesystem->delete($target_dir . $entry, true)) {
        $restore_failures[] = $entry;
      }
    }

    // put the previous version back
    foreach ($backed_up as $entry) {
      if (\file_exists($target_dir . $entry) || ! $this->move($backup_dir . $entry, $target_dir . $entry)) {
        $restore_failures[] = $entry;
      }
    }

    $wp_filesystem->delete($staging_dir, true);

    if ($restore_failures) {
      // keep the backup directory so the previous version can be recovered manually
      return new \WP_Error(
        'ionos_core_update_rollback_failed',
        sprintf("%s; rollback of '%s' failed, previous version kept in '%s'", $error, implode("', '", $restore_failures), $backup_dir)
      );
    }

    $wp_filesystem->delete($backup_dir, true);

    return new \WP_Error('ionos_core_update_failed', $error . '; previous version restored');
  }

  /**
   * renames a file or directory (atomic on the same filesystem). Separate method to be able to simulate failures in tests.
   */
  protected function move(string $from, string $to): bool
  {
    return @rename($from, $to);
  }
}

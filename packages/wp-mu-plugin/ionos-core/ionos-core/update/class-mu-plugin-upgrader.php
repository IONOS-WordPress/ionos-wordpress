<?php

namespace ionos\ionos_core;

defined('ABSPATH') || exit();

require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/file.php';

class MU_Plugin_Upgrader extends \WP_Upgrader
{
  /**
   * entries of the package that make up the mu-plugin (loader file and plugin directory)
   */
  protected const PLUGIN_ENTRIES = ['ionos-core', 'ionos-core.php'];

  /**
   * the entry of PLUGIN_ENTRIES that has to be a directory (all others have to be regular files)
   */
  protected const PLUGIN_DIRECTORY = 'ionos-core';

  /**
   * state of a running swap (null if no swap is in progress). Used to roll back if the request dies mid-swap.
   *
   * 'backed_up' and 'installed' list the entries whose move was *attempted*. They are recorded before the move,
   * because the request can die (or the move can fail half way) after the disk changed but before the move returned.
   *
   * @var null|array{target_dir: string, staging_dir: string, backup_dir: string, backed_up: string[], installed: string[]}
   */
  private ?array $swap = null;

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
   * replaces the installed plugin entries by the ones from $source_dir.
   *
   * the new version is staged next to the live plugin first, the live entries are then moved
   * to a backup directory and the staged entries are moved in place. While swapping the site is
   * in maintenance mode.
   * if anything fails - including the request dying in the middle of the swap (see restore_on_shutdown()) -
   * the previous version is restored.
   *
   * @return true|\WP_Error
   */
  public function install(string $source_dir, string $target_dir)
  {
    global $wp_filesystem;

    $source_dir = \trailingslashit($source_dir);
    $target_dir = \trailingslashit($target_dir);

    // the swap works on local paths, which only match the paths seen by $wp_filesystem if it accesses the disk directly
    if ('direct' !== $wp_filesystem->method) {
      return new \WP_Error(
        'ionos_core_update_unsupported_filesystem',
        sprintf("Updating requires the 'direct' filesystem method (is '%s')", $wp_filesystem->method)
      );
    }

    foreach (static::PLUGIN_ENTRIES as $entry) {
      if (! $this->entry_exists($source_dir, $entry)) {
        return new \WP_Error('ionos_core_update_invalid_package', "Package does not contain '{$entry}'");
      }
    }

    if ('' === (\get_file_data($source_dir . 'ionos-core.php', [
      'version' => 'Version',
    ])['version'] ?? '')) {
      return new \WP_Error('ionos_core_update_invalid_package', "Package's ionos-core.php has no version header");
    }

    // staging needs as much space as the package, the factor 2 leaves some headroom
    $required_space = 2 * $this->directory_size($source_dir);
    $free_space     = $this->free_space($target_dir);
    if (null !== $free_space && $free_space < $required_space) {
      return new \WP_Error(
        'ionos_core_update_disk_space',
        sprintf('Not enough disk space to update (required: %d bytes, available: %d bytes)', $required_space, $free_space)
      );
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
      if (! $this->entry_exists($staging_dir, $entry)) {
        $wp_filesystem->delete($staging_dir, true);
        return new \WP_Error('ionos_core_update_stage_failed', "Staging of '{$entry}' failed");
      }
    }

    // swap: live -> backup, staging -> live. $this->swap tracks what has to be reverted.
    $this->swap = [
      'target_dir'  => $target_dir,
      'staging_dir' => $staging_dir,
      'backup_dir'  => $backup_dir,
      'backed_up'   => [],
      'installed'   => [],
    ];
    // also roll back if the request is killed (fatal error, timeout, ...) before the swap is finished
    \add_action('shutdown', [$this, 'restore_on_shutdown'], 10, 0);
    $this->set_maintenance_mode(true);

    $error = null;

    if (! $wp_filesystem->mkdir($backup_dir)) {
      $error = 'Could not create backup directory';
    }

    if (! $error) {
      foreach (static::PLUGIN_ENTRIES as $entry) {
        if (\file_exists($target_dir . $entry)) {
          $this->swap['backed_up'][] = $entry;
          if (! $this->move($target_dir . $entry, $backup_dir . $entry)) {
            $error = "Could not back up '{$entry}'";
            break;
          }
        }
      }
    }

    if (! $error) {
      foreach (static::PLUGIN_ENTRIES as $entry) {
        $this->swap['installed'][] = $entry;
        if (! $this->move($staging_dir . $entry, $target_dir . $entry)) {
          $error = "Could not install '{$entry}'";
          break;
        }
      }
    }

    if ($error) {
      return $this->rollback($error);
    }

    $this->swap = null;
    \remove_action('shutdown', [$this, 'restore_on_shutdown'], 10);
    $this->set_maintenance_mode(false);

    $wp_filesystem->delete($staging_dir, true);
    $wp_filesystem->delete($backup_dir, true);

    return true;
  }

  /**
   * shutdown handler: restores the previous version if the request ended while a swap was still in progress
   * (e.g. fatal error or timeout). Does nothing if no swap is in progress.
   */
  public function restore_on_shutdown(): void
  {
    if (null === $this->swap) {
      return;
    }

    $result = $this->rollback('Update was interrupted');
    \error_log('ionos-core: ' . $result->get_error_message());
  }

  /**
   * restores the previous plugin version after a failed or interrupted swap.
   */
  private function rollback(string $error): \WP_Error
  {
    global $wp_filesystem;

    [
      'target_dir'  => $target_dir,
      'staging_dir' => $staging_dir,
      'backup_dir'  => $backup_dir,
      'backed_up'   => $backed_up,
      'installed'   => $installed,
    ] = $this->swap;

    $this->swap = null;
    \remove_action('shutdown', [$this, 'restore_on_shutdown'], 10);

    $restore_failures = [];

    // remove what was (or may have been - partially) installed from the new version.
    // All live entries are backed up before the first install, so anything in the way here belongs to the new version.
    foreach ($installed as $entry) {
      if (\file_exists($target_dir . $entry) && ! $wp_filesystem->delete($target_dir . $entry, true)) {
        $restore_failures[] = $entry;
      }
    }

    // put the previous version back. The disk is inspected instead of trusting the bookkeeping:
    // - backup present, live entry missing: move it back
    // - backup missing, live entry present: the backup move never happened, the live entry is the previous version
    // - anything else cannot be confirmed (e.g. half copied backup) and keeps the backup directory
    foreach ($backed_up as $entry) {
      $has_backup = \file_exists($backup_dir . $entry);
      $has_live   = \file_exists($target_dir . $entry);

      if ($has_backup && ! $has_live) {
        if ($this->move($backup_dir . $entry, $target_dir . $entry)) {
          continue;
        }
      } elseif (! $has_backup && $has_live) {
        continue;
      }

      $restore_failures[] = $entry;
    }

    $this->set_maintenance_mode(false);
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
   * moves a file or directory using WordPress core's mechanics (rename, falls back to copy+delete).
   * Separate method to be able to simulate failures in tests.
   */
  protected function move(string $from, string $to): bool
  {
    global $wp_filesystem;

    if (\is_dir($from)) {
      return true === \move_dir($from, $to, false);
    }

    return (bool) $wp_filesystem->move($from, $to, false);
  }

  /**
   * free disk space in bytes of the filesystem containing $dir or null if unknown.
   * Separate method to be able to simulate a full disk in tests.
   */
  protected function free_space(string $dir): ?float
  {
    $free = @\disk_free_space($dir);
    return false === $free ? null : $free;
  }

  /**
   * enables/disables the maintenance mode (core's .maintenance file).
   * Separate method to be able to avoid touching the site in tests.
   */
  protected function set_maintenance_mode(bool $enable): void
  {
    $this->maintenance_mode($enable);
  }

  /**
   * whether the package/staging directory contains $entry with the expected type (directory or regular file)
   */
  private function entry_exists(string $dir, string $entry): bool
  {
    return static::PLUGIN_DIRECTORY === $entry ? \is_dir($dir . $entry) : \is_file($dir . $entry);
  }

  private function directory_size(string $dir): int
  {
    $size  = 0;
    $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
      $size += $file->getSize();
    }
    return $size;
  }
}

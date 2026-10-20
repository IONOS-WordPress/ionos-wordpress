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

    $result = \copy_dir($working_dir, WPMU_PLUGIN_DIR);

    if (\is_wp_error($result)) {
      return $result;
    }

    $plugin_dir  = \trailingslashit(WPMU_PLUGIN_DIR) . 'ionos-core/';
    $package_dir = \trailingslashit($working_dir) . 'ionos-core/';

    // Guard: never remove files if the package does not contain the ionos-core directory
    if (\is_dir($plugin_dir) && \is_dir($package_dir)) {
      $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($plugin_dir, \FilesystemIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST
      );
      $stale_dirs = [];

      foreach ($files as $file) {
        $relative_path = substr($file->getPathname(), strlen($plugin_dir));
        if (\file_exists($package_dir . $relative_path)) {
          continue;
        }

        if ($file->isDir()) {
          $stale_dirs[] = $file->getPathname();
          continue;
        }

        $wp_filesystem->delete($file->getPathname());
      }

      // CHILD_FIRST lists subdirectories before their parents, so each directory is empty when it is removed
      foreach ($stale_dirs as $dir) {
        $wp_filesystem->rmdir($dir);
      }
    }

    $wp_filesystem->delete($working_dir, true);

    return true;
  }
}

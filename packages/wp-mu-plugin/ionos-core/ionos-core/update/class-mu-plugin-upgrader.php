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

    $result = \copy_dir($working_dir . '/ionos-core/', WPMU_PLUGIN_DIR, ['README.md', 'CHANGELOG.md']);
    $wp_filesystem->delete($working_dir, true);

    if (\is_wp_error($result)) {
      return $result;
    }

    return true;
  }
}

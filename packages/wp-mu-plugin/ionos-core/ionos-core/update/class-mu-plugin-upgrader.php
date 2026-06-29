<?php

/**
 * MU plugin upgrader that installs a zip package into WPMU_PLUGIN_DIR.
 */

namespace ionos\ionos_core;

require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/file.php';

class MU_Plugin_Upgrader extends \WP_Upgrader
{
  public function __construct()
  {
    parent::__construct(new \Automatic_Upgrader_Skin());
  }

  public function upgrade(string $package_url): true|\WP_Error
  {
    \WP_Filesystem();

    $this->init();

    $result = $this->run([
      'package'           => $package_url,
      'destination'       => WPMU_PLUGIN_DIR . '/ionos-core',
      'clear_destination' => true,
      'clear_working'     => true,
      'hook_extra'        => ['type' => 'plugin', 'action' => 'update'],
    ]);

    if (\is_wp_error($result)) {
      return $result;
    }

    if (!$result) {
      return new \WP_Error('mu_plugin_upgrade_failed', 'MU plugin upgrade failed with no result.');
    }

    return true;
  }
}

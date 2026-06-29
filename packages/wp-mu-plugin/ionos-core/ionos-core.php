<?php

/**
 * Plugin Name:       Ionos Core
 * Description:       Core functionality for IONOS WordPress projects.
 * Plugin URI:        https://github.com/IONOS-WordPress/ionos-wordpress/tree/main/packages/wp-mu-plugin/ionos-core
 * Requires at least:  6.0
 * Version:           0.1.0
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /ionos-core/languages
 * Text Domain:       ionos-core
 */

namespace ionos\ionos_core;

defined('ABSPATH') || exit();

require_once __DIR__ . '/ionos-core/index.php';

const INFO_JSON_URL   = 'https://tom-rockstar.de/ionos-core/ionos-core-info.json';

\add_action('wp_update_plugins', function (): void {
  $info = \wp_remote_get(INFO_JSON_URL, [
    'timeout' => 5,
  ]);

  if (\is_wp_error($info)) {
    \error_log('ionos-core: Error fetching update info: ' . $info->get_error_message());
    return;
  }

  $info_data    = json_decode(\wp_remote_retrieve_body($info), true, 512, JSON_THROW_ON_ERROR);
  $latest       = $info_data['version']      ?? null;
  $download_url = $info_data['download_url'] ?? null;

  if (! $latest || ! $download_url) {
    \error_log('ionos-core: Update info response is missing version or download_url.');
    return;
  }

  $current_version = \get_file_data(__FILE__, ['version' => 'Version'])['version'] ?? null;
  if (! \version_compare($latest, $current_version, '>')) {
    return;
  }

  $result = (new MU_Plugin_Upgrader())->upgrade($download_url);

  if (\is_wp_error($result)) {
    \error_log('ionos-core: Update failed: ' . $result->get_error_message());
    return;
  }

});

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

    // Use copy_dir over the existing directory rather than delete+recreate, which
    // fails in Docker where the parent mu-plugins/ dir is owned by the host user.
    $result = $this->run([
      'package'                     => $package_url,
      'destination'                 => WPMU_PLUGIN_DIR . '/ionos-core',
      'clear_destination'           => false,
      'abort_if_destination_exists' => false,
      'clear_working'               => true,
      'hook_extra'                  => [
        'type'   => 'plugin',
        'action' => 'update',
      ],
    ]);

    if (\is_wp_error($result)) {
      return $result;
    }

    if (! $result) {
      return new \WP_Error('mu_plugin_upgrade_failed', 'MU plugin upgrade failed with no result.');
    }

    return true;
  }
}

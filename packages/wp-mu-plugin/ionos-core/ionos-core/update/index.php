<?php

/**
 * Checks for and applies MU plugin updates from the remote info endpoint.
 */

namespace ionos\ionos_core;

require_once __DIR__ . '/class-mu-plugin-upgrader.php';

function check_for_updates(): void
{
  $info = \wp_remote_get(INFO_JSON_URL, ['timeout' => 5]);

  if (\is_wp_error($info)) {
    \error_log('ionos-core: Error fetching update info: ' . $info->get_error_message());
    return;
  }

  $info_data    = json_decode(\wp_remote_retrieve_body($info), true, 512, JSON_THROW_ON_ERROR);
  $latest       = $info_data['version'] ?? null;
  $download_url = $info_data['download_url'] ?? null;

  if (!$latest || !$download_url) {
    \error_log('ionos-core: Update info response is missing version or download_url.');
    return;
  }

  if (!\version_compare($latest, CURRENT_VERSION, '>')) {
    \error_log('ionos-core: No update available. Current: ' . CURRENT_VERSION . ', Latest: ' . $latest);
    return;
  }

  \error_log('ionos-core: Updating from ' . CURRENT_VERSION . ' to ' . $latest . '.');

  $result = (new MU_Plugin_Upgrader())->upgrade($download_url);

  if (\is_wp_error($result)) {
    \error_log('ionos-core: Update failed: ' . $result->get_error_message());
    return;
  }

  \error_log('ionos-core: Update to ' . $latest . ' completed successfully.');
}

\add_action('wp_update_plugins', __NAMESPACE__ . '\\check_for_updates');

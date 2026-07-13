<?php

namespace ionos\ionos_core;

defined('ABSPATH') || exit();

const INFO_JSON_URL = 'https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Flatest/ionos-core-info.json';

require_once __DIR__ . '/class-mu-plugin-upgrader.php';

\add_action('wp_update_plugins', function (): void {
  $response = \wp_remote_get(INFO_JSON_URL, [
    'timeout' => 5,
  ]);

  if (\is_wp_error($response)) {
    \error_log('ionos-core: Error fetching update info: ' . $response->get_error_message());
    return;
  }

  try {
    $info = json_decode(\wp_remote_retrieve_body($response), true, 512, JSON_THROW_ON_ERROR);
  } catch (\JsonException $e) {
    \error_log('ionos-core: Failed to parse update info: ' . $e->getMessage());
    return;
  }

  $latest       = $info['version']      ?? null;
  $package      = $info['package']      ?? null;


  if (! $latest || ! $package) {
    \error_log('ionos-core: Update info response is missing version or package.');
    return;
  }

  $current_version = \get_file_data(__DIR__ . '/../../ionos-core.php', [
    'version' => 'Version',
  ])['version'] ?? null;

  if (! \version_compare($latest, $current_version, '>')) {
    return;
  }

  $result = (new MU_Plugin_Upgrader())->upgrade($package);

  if (\is_wp_error($result)) {
    \error_log('ionos-core: Update failed: ' . $result->get_error_message());
  }
});

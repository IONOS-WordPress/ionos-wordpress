<?php

namespace ionos\ionos_core;

function check_for_updates()
{
  $info = wp_remote_get(INFO_JSON_URL, [
    'timeout' => 5,
  ]);

  if (is_wp_error($info)) {
    error_log('Error fetching update info: ' . $info->get_error_message());
    return;
  }

  $info_body = wp_remote_retrieve_body($info);
  $info_data = json_decode($info_body, true);

  if (json_last_error() !== JSON_ERROR_NONE) {
    error_log('Error decoding update info JSON: ' . json_last_error_msg());
    return;
  }

  $current_version = CURRENT_VERSION; // Current version of the plugin
  $latest_version  = $info_data['version'] ?? null;

  if ($latest_version && version_compare($latest_version, $current_version, '>')) {
    error_log("Update available: Current version: {$current_version}, Latest version: {$latest_version}");
  } else {
    error_log("No update available. Current version: {$current_version}, Latest version: {$latest_version}");
  }
}

add_action('admin_init', __NAMESPACE__ . '\\check_for_updates');

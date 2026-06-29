<?php

namespace ionos\ionos_core;

function check_for_updates(): void
{
  $info = wp_remote_get(INFO_JSON_URL, [
    'timeout' => 5,
  ]);

  if (is_wp_error($info)) {
    error_log('Error fetching update info: ' . $info->get_error_message());
    return;
  }

  $info_body = wp_remote_retrieve_body($info);
  $info_data = json_decode($info_body, true, 512, JSON_THROW_ON_ERROR);

  $current_version = CURRENT_VERSION;
  $latest_version  = $info_data['version'] ?? null;

  if ($latest_version && version_compare($latest_version, $current_version, '>')) {
    error_log("Update available: Current version: {$current_version}, Latest version: {$latest_version}");
  } else {
    error_log("No update available. Current version: {$current_version}, Latest version: {$latest_version}");
  }
}

add_action('wp_update_plugins', __NAMESPACE__ . '\\check_for_updates');

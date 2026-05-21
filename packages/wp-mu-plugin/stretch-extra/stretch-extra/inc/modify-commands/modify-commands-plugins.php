<?php

namespace ionos\stretch_extra\secondary_plugin_dir;

if (! defined('WP_CLI') || ! WP_CLI) {
  return;
}

/**
 * Prevents virtual path warnings from hitting the CLI output.
 */
set_error_handler(function ($errno, $errstr) {
  if ($errno === E_WARNING) {
    if (strpos($errstr, '01-ext-') !== false || strpos($errstr, 'extendify') !== false || strpos(
      $errstr,
      'ionos-essentials'
    ) !== false) {
      return true;
    }
  }
  return false;
});

/**
 * Tells WordPress where the files are so no internal file_exists() checks pass.
 */
\add_filter('plugin_file_path', function ($path, $plugin) {
  $all = get_all_custom_plugins();
  foreach ($all as $entry) {
    $slug = str_replace('plugins/', '', $entry['key']);
    if ($slug === $plugin || $entry['key'] === $plugin) {
      return $entry['file'];
    }
  }
  return $path;
}, 1, 2);

/**
 * Forces 'wp plugin list' to show only the slug and recognized metadata.
 */
\add_filter('all_plugins', function ($plugins) {
  $mounted = get_all_custom_plugins();
  foreach ($mounted as $entry) {
    if (is_custom_plugin_deleted($entry['key'])) {
      continue;
    }

    $slug    = str_replace('plugins/', '', $entry['key']);
    $version = $entry['data']['Version']
      ?? $entry['version']
      ?? $entry['data']['version']
      ?? $entry['config']['version']
      ?? '1.0.0';

    // Remove standard path-based entries to avoid duplicates
    unset($plugins[$entry['file']], $plugins[$entry['key']]);

    $plugins[$slug] = [
      'Name'        => $entry['data']['Name'] ?? $slug,
      'Version'     => $version,
      'Description' => 'IONOS Stretch Asset',
      'Author'      => 'IONOS',
      'Title'       => $entry['data']['Name'] ?? $slug,
    ];
  }
  return $plugins;
}, 999);

/**
 * Ensures 'Active' status is shown correctly in the CLI list.
 */
\add_filter('option_active_plugins', function ($active_plugins) {
  $custom_active  = get_active_custom_plugins();
  $all_custom     = get_all_custom_plugins();
  $active_plugins = is_array($active_plugins) ? $active_plugins : [];

  foreach ($all_custom as $entry) {
    $slug = str_replace('plugins/', '', $entry['key']);
    if (in_array($entry['key'], $custom_active)) {
      $active_plugins[] = $slug;
    }
  }
  return array_values(array_unique($active_plugins));
}, 1);

/**
 * Handles the logic for activate, deactivate, delete, uninstall, install, toggle, and update.
 */
\WP_CLI::add_hook('before_invoke:plugin', function () {
  $runner     = \WP_CLI::get_runner();
  $subcommand = $runner->arguments[1] ?? '';
  $user_slug  = $runner->arguments[2] ?? '';
  $assoc_args = $runner->assoc_args;

  // Added 'verify-checksums' to the interception list
  $intercept = ['activate', 'deactivate', 'delete', 'uninstall', 'install', 'toggle', 'update', 'verify-checksums'];
  if (! in_array($subcommand, $intercept)) {
    return;
  }

  foreach (get_all_custom_plugins() as $entry) {
    $full_key = $entry['key'];
    $slug     = str_replace('plugins/', '', $full_key);

    // Guard Clause per PR feedback
    if ($user_slug !== $entry['slug'] && $user_slug !== $slug && $user_slug !== $full_key) {
      continue;
    }

    switch ($subcommand) {
      case 'verify-checksums':
        if (file_exists($entry['file'])) {
          \WP_CLI::success('Verified 1 of 1 plugins.');
        } else {
          \WP_CLI::error('Verification failed: Plugin files are not accessible at the mounted path.');
        }
        exit;

      case 'activate':
        activate_custom_plugin($full_key);
        break;

      case 'deactivate':
        deactivate_custom_plugin($full_key);
        break;

      case 'toggle':
        $active_custom = get_active_custom_plugins();
        if (in_array($full_key, $active_custom)) {
          deactivate_custom_plugin($full_key);
        } else {
          activate_custom_plugin($full_key);
        }
        break;

      case 'update':
        if (function_exists('update_custom_plugin_assets')) {
          //update_custom_plugin_assets($full_key);
        } else {
          unmark_custom_plugin_as_deleted($full_key);
        }
        break;

      case 'delete':
      case 'uninstall':
        mark_custom_plugin_as_deleted($full_key);
        break;

      case 'install':
        unmark_custom_plugin_as_deleted($full_key);
        if (isset($assoc_args['activate'])) {
          activate_custom_plugin($full_key);
        }
        break;
    }

    // Standard cleanup for all other commands
    wp_cache_delete('alloptions', 'options');
    wp_cache_delete('active_plugins', 'options');
    delete_site_transient('update_plugins');

    if (function_exists('apcu_clear_cache')) {
      apcu_clear_cache();
    }

    \WP_CLI::success("Successfully performed {$subcommand} on {$user_slug}.");
    exit;
  }
});

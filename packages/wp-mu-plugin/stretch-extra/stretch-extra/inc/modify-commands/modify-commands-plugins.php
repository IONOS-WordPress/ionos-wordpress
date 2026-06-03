<?php

namespace ionos\stretch_extra\secondary_plugin_dir;

defined('ABSPATH') || exit();

if (! defined('WP_CLI') || ! WP_CLI) {
  return;
}

\add_filter('plugin_file_path', function ($path, $plugin) {
  $all = get_all_custom_plugins();
  foreach ($all as $entry) {
    // Check if the plugin has been marked as deleted
    if (is_custom_plugin_deleted($entry['key'])) {
      continue;
    }

    $slug = str_replace('plugins/', '', $entry['key']);
    if ($slug === $plugin || $entry['key'] === $plugin) {
      return $entry['file'];
    }
  }
  return $path;
}, 1, 2);

\add_filter('all_plugins', function ($plugins) {
  $mounted = get_all_custom_plugins();
  foreach ($mounted as $entry) {
    if (is_custom_plugin_deleted($entry['key'])) {
      continue;
    }

    $slug = str_replace('plugins/', '', $entry['key']);

    unset($plugins[$entry['file']], $plugins[$entry['key']]);

    if (file_exists($entry['file'])) {
      if (! function_exists('get_plugin_data')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
      }
      $plugins[$slug] = \get_plugin_data($entry['file']);
    } else {
      $plugins[$slug] = [
        'Name'        => $entry['data']['Name'] ?? $slug,
        'Version'     => '1.0.0',
        'Description' => __('IONOS Stretch Asset', 'stretch-extra'),
        'Author'      => __('IONOS', 'stretch-extra'),
        'Title'       => $entry['data']['Name'] ?? $slug,
      ];
    }
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

$intercept_subcommands = [
  'activate',
  'deactivate',
  'delete',
  'uninstall',
  'install',
  'toggle',
  'update',
  'verify-checksums',
];

foreach ($intercept_subcommands as $subcommand) {
  \WP_CLI::add_hook("before_invoke:plugin:{$subcommand}", function ($args, $assoc_args) use ($subcommand) {
    $custom_plugins   = get_all_custom_plugins();
    $processed_custom = false;
    $unprocessed_args = [];

    foreach ($args as $user_slug) {
      $matched = false;

      foreach ($custom_plugins as $entry) {
        $full_key = $entry['key'];
        $slug     = str_replace('plugins/', '', $full_key);

        if ($user_slug !== $entry['slug'] && $user_slug !== $slug && $user_slug !== $full_key) {
          continue;
        }

        $matched          = true;
        $processed_custom = true;

        switch ($subcommand) {
          case 'verify-checksums':
            if (file_exists($entry['file'])) {
              \WP_CLI::success(__('Verified 1 of 1 plugins.', 'stretch-extra'));
            } else {
              \WP_CLI::error(
                __('Verification failed: Plugin files are not accessible at the mounted path.', 'stretch-extra')
              );
            }
            break;

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
            \WP_CLI::error(__('Update not supported for mounted plugins.', 'stretch-extra'));
            break;

          case 'delete':
          case 'uninstall':
            mark_custom_plugin_as_deleted($full_key);
            break;

          case 'install':
            if (is_custom_plugin_deleted($full_key)) {
              unmark_custom_plugin_as_deleted($full_key);
            } else {
              \WP_CLI::warning(
                sprintf(__('Plugin "%s" is already installed and active.', 'stretch-extra'), $user_slug)
              );
              break;
            }

            if (isset($assoc_args['activate'])) {
              activate_custom_plugin($full_key);
            }
            break;
        }

        wp_cache_delete('alloptions', 'options');
        delete_site_transient('update_plugins');

        if (function_exists('wp_cache_flush_runtime')) {
          wp_cache_flush_runtime();
        }

        \WP_CLI::success(
          sprintf(__('Successfully performed %1$s on %2$s.', 'stretch-extra'), $subcommand, $user_slug)
        );

        break;
      }

      if (! $matched) {
        $unprocessed_args[] = $user_slug;
      }
    }

    if ($processed_custom) {
      if (empty($unprocessed_args)) {
        exit;
      }
      \WP_CLI::get_runner()->arguments = array_merge(
        [\WP_CLI::get_runner()->arguments[0], $subcommand],
        $unprocessed_args
      );

    }
  });
}

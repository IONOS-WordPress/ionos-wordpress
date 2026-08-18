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
  if (\apply_filters('ionos_stretch_extra_suppress_custom_plugins', false)) {
    return $plugins;
  }

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

// bulk ("--all") variants only make sense for these; the rest always name a slug.
$bulk_capable_subcommands = ['activate', 'deactivate', 'toggle'];

// `before_invoke:plugin <subcommand>` only ever passes the command name string
// (see WP_CLI\Dispatcher\Subcommand::invoke()'s `do_hook("before_invoke:{$cmd}", $cmd)`)
// - not the actual $args/$assoc_args - so the real slugs/flags have to be read
// back out of the raw CLI invocation, same as plugin-block-list.php does.
$extract_plugin_args_from_argv = function ($subcommand) {
  $argv          = $_SERVER['argv'] ?? [];
  $plugins       = [];
  $found_command = false;
  $all           = false;

  foreach ($argv as $arg) {
    if ($found_command) {
      if ($arg === '--all') {
        $all = true;
      } elseif (! str_starts_with($arg, '--')) {
        $plugins[] = $arg;
      }
    }
    if ($arg === $subcommand) {
      $found_command = true;
    }
  }

  return [$plugins, $all];
};

foreach ($intercept_subcommands as $subcommand) {
  \WP_CLI::add_hook("before_invoke:plugin {$subcommand}", function () use (
    $subcommand,
    $bulk_capable_subcommands,
    $extract_plugin_args_from_argv
  ) {
    $custom_plugins             = get_all_custom_plugins();
    [$user_slugs, $bulk_all]    = $extract_plugin_args_from_argv($subcommand);

    if ($bulk_all) {
      if (! in_array($subcommand, $bulk_capable_subcommands, true)) {
        return;
      }

      // apply the bulk action to every mounted plugin ourselves, then let the
      // real "--all" continue for genuine filesystem plugins - suppressing our
      // own all_plugins injection (via the ionos_stretch_extra_suppress_custom_plugins
      // filter below) so wp-cli's own "--all" doesn't also try (and fail) to
      // activate/deactivate them by file path.
      foreach ($custom_plugins as $entry) {
        if (is_custom_plugin_deleted($entry['key'])) {
          continue;
        }

        $is_active = in_array($entry['key'], get_active_custom_plugins(), true);

        if ($subcommand === 'activate' && ! $is_active) {
          activate_custom_plugin($entry['key']);
        } elseif ($subcommand === 'deactivate' && $is_active) {
          deactivate_custom_plugin($entry['key']);
        } elseif ($subcommand === 'toggle') {
          $is_active ? deactivate_custom_plugin($entry['key']) : activate_custom_plugin($entry['key']);
        }
      }

      \add_filter('ionos_stretch_extra_suppress_custom_plugins', '__return_true');

      wp_cache_delete('alloptions', 'options');
      delete_site_transient('update_plugins');
      if (function_exists('wp_cache_flush_runtime')) {
        wp_cache_flush_runtime();
      }

      return;
    }

    $processed_custom = false;
    $unprocessed_args = [];

    foreach ($user_slugs as $user_slug) {
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

            break;

          default:
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

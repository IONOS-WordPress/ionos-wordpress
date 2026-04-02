<?php

/**
 * MU Plugin: Block disallowed plugins from UI and WP-CLI
 */
defined('ABSPATH') || exit();
/**
 * List of disallowed plugins (plugin_file => reason)
 */
function get_disallowed_plugins()
{
  return [
    'bwp-minify/bwp-minify.php',
    'e-mail-broadcasting/e-mail-broadcasting.php',
    'send-email-from-admin/send-email-from-admin.php',
    'mailit/mailit.php',
    'nginx-helper/nginx-helper.php',
    'stopbadbots/stopbadbots.php',
    'w3-total-cache/w3-total-cache.php',
    'wp-fastest-cache/wpFastestCache.php',
    'wp-super-cache/wp-cache.php',
    'wp-rest-api-log/wp-rest-api-log.php',
    'website-file-changes-monitor/website-file-changes-monitor.php',
  ];
}

function get_blocked_plugins_slug(){
    return array_map('dirname', get_disallowed_plugins());
}

function get_plugin_slug($plugin) {
    return strpos($plugin, '/') !== false ? dirname($plugin) : $plugin;
}

/**
 * Disable install button for disallowed plugins
 */
function disable_plugin_install_link($action_links, $plugin)
{
  $disallowed_slugs = get_blocked_plugins_slug();

  if (in_array($plugin['slug'], $disallowed_slugs, true)) {
    return [
      '<a class="install-now button button-disabled" href="#">' . __('Not Supported', 'stretch-extra') . '</a>',
    ];
  }

  return $action_links;
}
add_filter('plugin_install_action_links', 'disable_plugin_install_link', 0, 2);

/**
 * Disable activate button for disallowed plugins
 */
function disable_plugin_activate_link($actions, $plugin_file)
{
  $disallowed = get_disallowed_plugins();

  if (isset($actions['activate']) && in_array($plugin_file, $disallowed, true)) {
    $actions['activate'] = __('Disabled', 'stretch-extra');
    unset($actions['edit']);
  }

  return $actions;
}
add_filter('plugin_action_links', 'disable_plugin_activate_link', 10, 2);
add_filter('network_admin_plugin_action_links', 'disable_plugin_activate_link', 10, 2);

/**
 * Deactivate disallowed plugins if they are active
 */
function deactivate_disallowed_plugins()
{
  // Make sure plugin functions are available
  require_once ABSPATH . 'wp-admin/includes/plugin.php';

  $disallowed = get_disallowed_plugins();

  foreach ($disallowed as $plugin_file) {
    if (is_plugin_active($plugin_file)) {

      // Get plugin data (real name from header)
      $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file);
      $plugin_name = ! empty($plugin_data['Name'])
        ? $plugin_data['Name']
        : $plugin_file; // fallback

      deactivate_plugins($plugin_file);

      add_action('admin_notices', function () use ($plugin_name) {

        $message = sprintf(
          __('The use of "%s" is not allowed and cannot be activated. Uninstall is recommended.', 'stretch-extra'),
          $plugin_name
        );

        echo '<div class="notice notice-error is-dismissible"><p>' .
          wp_kses_post($message) .
          '</p></div>';
      });
    }
  }
}
add_action('admin_init', 'deactivate_disallowed_plugins', 0);

/**
 * Disallow installation of blocked plugins via the installer
 */
function block_disallowed_post_install($true, $hook_extra, $result)
{
  $disallowed = get_disallowed_plugins();

  if (empty($result['destination']) || ! is_dir($result['destination'])) {
    return $true;
  }

  $plugin_folder = basename($result['destination']);
  $files         = scandir($result['destination']);

  // Normal text after "Unpacking the package…"
  wp_register_style('stretch-extra-inline', false);
  wp_enqueue_style('stretch-extra-inline');

  wp_add_inline_style('stretch-extra-inline', '
      .zip-upload-text {
          margin: 0 0 8px 0;
          font-style: italic;
          color: #555;
      }
  ');

  echo '<p class="zip-upload-text">' . esc_html__(
    'Validating against blocked plugins…',
    'stretch-extra'
  ) . '</p>';

  foreach ($files as $file) {

    // skip non-PHP files
    if (substr($file, -4) !== '.php') {
      continue;
    }

    $plugin_file = "{$plugin_folder}/{$file}";

    // skip allowed plugins
    if (! in_array($plugin_file, $disallowed, true)) {
      continue;
    }

    $it         = new RecursiveDirectoryIterator($result['destination'], RecursiveDirectoryIterator::SKIP_DOTS);
    $files_iter = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

    foreach ($files_iter as $fileinfo) {
      $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
      $todo($fileinfo->getRealPath());
    }

    rmdir($result['destination']);

    return new WP_Error('plugin_blocked', error_notice_for_blocked_plugin());
  }

  return $true;
}
if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
    add_filter( 'upgrader_post_install', 'block_disallowed_post_install', 10, 3 );
}

function error_notice_for_blocked_plugin()
{
  return '<div style="padding:12px; border-left:4px solid #d63638; background-color:rgba(214,54,56,0.05); margin:0 0 12px 0;">' .
                         '<p>' . sprintf(
                           /* translators: %s: link to blocked plugins list */
                           __('This plugin is not supported on our Managed WordPress platform. %s', 'stretch-extra'),
                           '<a href="' . admin_url(
                             'plugins.php'
                           ) . '">' . __('Full list of blocked plugins', 'stretch-extra') . '</a>'
                         ) . '</p>' .
                         '</div>';
}

// Only run in WP-CLI
if (defined('WP_CLI') && WP_CLI) {
    // Helper variables and functions to block plugin activation via WP-CLI for disallowed plugins
    $blocked_slugs = get_blocked_plugins_slug();
    $blocked_slug_map = array_flip($blocked_slugs);

    $get_plugin_slug = function($plugin) {
        return strpos($plugin, '/') !== false ? dirname($plugin) : $plugin;
    };

    $is_disallowed = function($plugin) use ($blocked_slug_map, $get_plugin_slug) {
        $slug = $get_plugin_slug($plugin);
        return isset($blocked_slug_map[$slug]);
    };

    // Extract plugin slugs from CLI command
    $extract_plugins_from_argv = function($command) {
        $argv = $_SERVER['argv'] ?? [];
        $plugins = [];
        $found_command = false;

        foreach ($argv as $arg) {
            // Start collecting after the command (activate / install)
            if ($found_command) {
                $plugins[] = $arg;
            }
            if ($arg === $command) {
                $found_command = true;
            }
        }

        return $plugins;
    };

    // Block "wp plugin activate"
    \WP_CLI::add_hook('before_invoke:plugin activate', function($args, $assoc_args = []) use ($is_disallowed, $extract_plugins_from_argv) {

        $plugins = $extract_plugins_from_argv('activate');

        foreach ($plugins as $plugin) {
            if ($is_disallowed($plugin)) {
                \WP_CLI::error(sprintf(
                    __('The use of "%s" is not allowed and cannot be activated. Uninstall is recommended.', 'stretch-extra'),
                    $plugin
                ));
            }
        }
    });

    // Block "wp plugin install --activate"
    \WP_CLI::add_hook('before_invoke:plugin install', function($args, $assoc_args = []) use ($is_disallowed) {

    $argv = $_SERVER['argv'] ?? [];

    // Only run blocking if --activate is present
    if (!in_array('--activate', $argv, true)) {
        return;
    }

    // Collect all plugin slugs after "install"
    $plugins = [];
    $found_install = false;
    foreach ($argv as $arg) {
        if ($found_install) {
            // stop at any option starting with --
            if (str_starts_with($arg, '--')) {
                break;
            }
            $plugins[] = $arg;
        }
        if ($arg === 'install') {
            $found_install = true;
        }
    }

    foreach ($plugins as $plugin) {
        if ($is_disallowed($plugin)) {
            \WP_CLI::error(sprintf(
                __('The use of "%s" is not allowed and cannot be activated via install. Uninstall is recommended.', 'stretch-extra'),
                $plugin
            ));
        }
    }
});
}

add_action('admin_enqueue_scripts', function ($hook) {
  if ($hook !== 'plugin-install.php') {
    return;
  }

  // Get the HTML notice from your PHP function
  $notice_html = str_replace(["\n", "'"], ['', "\\'"], error_notice_for_blocked_plugin());

  // Get the "Not Supported" text for the JS check
  $not_supported_text = __('Not Supported', 'stretch-extra');

  // Add inline vanilla JS
  wp_add_inline_script('jquery-core', "
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.plugin-card').forEach(function (card) {
                var pluginBox = card.querySelector('.plugin-card-top');
                if (!pluginBox) return;

                // Check if a button contains the 'Not Supported' text
                var buttons = pluginBox.querySelectorAll('.button-disabled');
                var hasNotSupported = Array.from(buttons).some(function(btn) {
                    return btn.textContent.includes('{$not_supported_text}');
                });

                if (hasNotSupported && !pluginBox.classList.contains('blocked-notice-added')) {
                    pluginBox.insertAdjacentHTML('afterbegin', '{$notice_html}');
                    pluginBox.classList.add('blocked-notice-added');
                }
            });
        });
    ");
});

add_action('admin_head', function () {
  $screen = get_current_screen();
  if ($screen && $screen->id === 'plugin-install') {
    echo '<style>
            /* Absolute elements have to be moved down, otherwise notice is not shown correct */
            .plugin-card-top.blocked-notice-added .plugin-icon,
            .plugin-card-top.blocked-notice-added .action-links {
                top: 100px !important;
            }
        </style>';
  }
});

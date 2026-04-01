<?php

/**
 * MU Plugin: Block disallowed plugins from UI and WP-CLI
 */

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

/**
 * Disable install button for disallowed plugins
 */
function disable_plugin_install_link($action_links, $plugin)
{
  $disallowed_slugs = array_map('dirname', get_disallowed_plugins());

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
add_filter('upgrader_post_install', 'block_disallowed_post_install', 10, 3);

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

if (defined('WP_CLI') && \WP_CLI) {
  add_filter(
    'validate_plugin_requirements',
    function ($met_requirements, $plugin) {
      $disallowed = get_disallowed_plugins();

      if (array_key_exists($plugin, $disallowed)) {
        return new WP_Error('plugin_not_supported', $disallowed[$plugin]);
      }

      return $met_requirements;
    },
    10,
    2
  );
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

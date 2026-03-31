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
    'bwp-minify/bwp-minify.php'                                     => __('BWP Minify (as of 1.3.3) is not ready for use. The plugin writes a configuration file that must be edited manually to support plugins and themes installed via symlinks. Because it breaks sites upon activation, we have automatically deactivated the plugin to keep your site working. In the interest of making BWP Minify compatible, we provided <a href="https://github.com/OddOneOut/bwp-minify/pull/67">this patch</a> to the author in May 2016. If you choose to fix the configuration file yourself, you may skip the automatic deactivation by renaming <code>bwp-minify/bwp-minify.php</code>.', 'stretch-extra'),
    'e-mail-broadcasting/e-mail-broadcasting.php'                   => __('The use of "E-Mail Broadcasting" is not allowed.', 'stretch-extra'),
    'send-email-from-admin/send-email-from-admin.php'               => __('The use of "Send Email From Admin" is not allowed.', 'stretch-extra'),
    'mailit/mailit.php'                                             => __('The use of "Mail It!" is not allowed.', 'stretch-extra'),
    'nginx-helper/nginx-helper.php'                                 => __('The use of Nginx Helper can interfere with caching, which is automatically provided for this site. Nginx Helper has been deactivated.', 'stretch-extra'),
    'stopbadbots/stopbadbots.php'                                   => __('The use of Stop Bad Bots is not allowed.', 'stretch-extra'),
    'w3-total-cache/w3-total-cache.php'                             => __('The use of W3 Total Cache can interfere with caching, which is automatically provided for this site. W3 Total Cache has been deactivated.', 'stretch-extra'),
    'wp-fastest-cache/wpFastestCache.php'                           => __('The use of WP Fastest Cache can interfere with caching, which is automatically provided for this site. WP Fastest Cache has been deactivated.', 'stretch-extra'),
    'wp-super-cache/wp-cache.php'                                   => __('The use of WP Super Cache can interfere with caching, which is automatically provided for this site. WP Super Cache has been deactivated.', 'stretch-extra'),
    'wp-rest-api-log/wp-rest-api-log.php'                           => __('WP REST API Log inflates post table size beyond normal usage levels.', 'stretch-extra'),
    'website-file-changes-monitor/website-file-changes-monitor.php' => __('Melapress File Monitor inflates the options table size beyond normal usage levels.', 'stretch-extra'),
  ];
}

/**
 * Disable install button for disallowed plugins
 */
function disable_plugin_install_link($action_links, $plugin)
{
  $disallowed_slugs = array_map('dirname', array_keys(get_disallowed_plugins()));

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

  if (isset($actions['activate']) && array_key_exists($plugin_file, $disallowed)) {
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
  $disallowed = get_disallowed_plugins();
  foreach ($disallowed as $plugin_file => $message) {
    if (is_plugin_active($plugin_file)) {
      deactivate_plugins($plugin_file);
      add_action('admin_notices', function () use ($message) {
        echo '<div class="notice notice-error is-dismissible"><p>' . wp_kses_post(
          __($message, 'stretch-extra')
        ) . '</p></div>';
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
  echo '<p style="margin:0 0 8px 0; font-style:italic; color:#555;">' . esc_html__(
    'Validating against blocked plugins…',
    'stretch-extra'
  ) . '</p>';

  foreach ($files as $file) {

    // Guard: skip non-PHP files
    if (substr($file, -4) !== '.php') {
      continue;
    }

    $plugin_file = "{$plugin_folder}/{$file}";

    // Guard: skip allowed plugins
    if (! isset($disallowed[$plugin_file])) {
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

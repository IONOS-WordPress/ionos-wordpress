<?php

namespace ionos\stretch_extra\secondary_plugin_dir;

use const ionos\stretch_extra\IONOS_CUSTOM_DIR;

defined('ABSPATH') || exit();

// Define custom plugins directory
const IONOS_CUSTOM_PLUGINS_PATH = 'plugins/';
const IONOS_CUSTOM_PLUGINS_DIR  = IONOS_CUSTOM_DIR . '/' . IONOS_CUSTOM_PLUGINS_PATH;

// Option name to store active custom plugins
const IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION = 'IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION';

// Option name to store deleted/hidden custom plugins
const IONOS_CUSTOM_DELETED_PLUGINS_OPTION = 'IONOS_CUSTOM_DELETED_PLUGINS_OPTION';

// @TODO: hack just for beta : on first run activate all custom plugins
// will be done in "spaceman" via sql: https://github.com/IONOS-Hosting/spaceman
// dont initialize in wp-cli calls to prevent issues with command line scripts in wp-env
defined('WP_CLI') || \add_action('plugins_loaded', function () {
  $is_initialized = \get_option(IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION);
  if ($is_initialized !== false) {
    return;
  }

  // Initialize the active plugins option as an empty array
  foreach (get_installed_custom_plugins() as $plugin_info) {
    // Activate all custom plugins by default on first run
    // @TODO: activate all in one update_option call

    // workaround for wp-cli : if a plugin with same name is already active (=>ionos-essentials), skip activation to avoid conflicts
    if (\is_plugin_active(str_replace('plugins/', '', $plugin_info['key']))) {
      continue;
    }

    activate_custom_plugin($plugin_info['key']);
  }
});

/**
 * Get list of deleted/hidden custom plugins
 */
function get_deleted_custom_plugins()
{
  return \get_option(IONOS_CUSTOM_DELETED_PLUGINS_OPTION, []);
}

/**
 * Mark a custom plugin as deleted/hidden
 */
function mark_custom_plugin_as_deleted($plugin_key)
{
  $deleted_plugins = get_deleted_custom_plugins();
  if (! in_array($plugin_key, $deleted_plugins, true)) {
    $deleted_plugins[] = $plugin_key;
    // Also deactivate the plugin if it was active
    deactivate_custom_plugin($plugin_key);
    \update_option(IONOS_CUSTOM_DELETED_PLUGINS_OPTION, $deleted_plugins, true);
  }
}

/**
 * Unmark a custom plugin as deleted/hidden
 */
function unmark_custom_plugin_as_deleted($plugin_key)
{
  $deleted_plugins = get_deleted_custom_plugins();
  $key             = array_search($plugin_key, $deleted_plugins, true);
  if ($key !== false) {
    unset($deleted_plugins[$key]);
    \update_option(IONOS_CUSTOM_DELETED_PLUGINS_OPTION, array_values($deleted_plugins), true);
  }
}

/**
 * Check if a custom plugin is marked as deleted
 */
function is_custom_plugin_deleted($plugin_key): bool
{
  $deleted_plugins = get_deleted_custom_plugins();
  return in_array($plugin_key, $deleted_plugins, true);
}

/**
 * Get list of active custom plugins (excluding deleted ones)
 */
function get_active_custom_plugins()
{
  $active_plugins  = \get_option(IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION, []);
  $deleted_plugins = get_deleted_custom_plugins();
  // Filter out any deleted plugins from the active list
  return array_diff($active_plugins, $deleted_plugins);
}

/**
 * Check if a custom plugin is active
 */
function is_custom_plugin_active($plugin_key): bool
{
  $active_plugins = get_active_custom_plugins();
  return in_array($plugin_key, $active_plugins, true);
}

/**
 * Activate a custom plugin
 */
function activate_custom_plugin($plugin_key)
{
  $active_plugins = get_active_custom_plugins();
  if (! in_array($plugin_key, $active_plugins, true)) {
    $active_plugins[] = $plugin_key;
    \update_option(IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION, $active_plugins, true);
  }
}

/**
 * Deactivate a custom plugin
 */
function deactivate_custom_plugin($plugin_key)
{
  $active_plugins = get_active_custom_plugins();
  $key            = array_search($plugin_key, $active_plugins, true);
  if ($key !== false) {
    unset($active_plugins[$key]);
    \update_option(IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION, array_values($active_plugins), true);
  }
}

/**
 * Get all custom plugins from the custom plugins directory
 */
function get_all_custom_plugins(): array
{
  static $all_custom_plugins = null;

  if ($all_custom_plugins === null) {
    $bundle_config      = require_once __DIR__ . '/stretch-extra-config.php';
    $all_custom_plugins = $bundle_config['plugins'];
  }

  return $all_custom_plugins;
}

/**
 * Get installed (not deleted) custom plugins
 */
function get_installed_custom_plugins(): array
{
  $all_custom_plugins = get_all_custom_plugins();
  // Filter out deleted plugins
  $deleted_plugins = get_deleted_custom_plugins();
  return array_filter($all_custom_plugins, function ($plugin_info) use ($deleted_plugins) {
    return ! in_array($plugin_info['key'], $deleted_plugins, true);
  });
}

/**
 * Inject activated custom plugins
 */
defined('WP_CLI') || \add_action('muplugins_loaded', function () {
  $custom_plugins = get_installed_custom_plugins();
  $active_plugins = get_active_custom_plugins();

  foreach ($custom_plugins as $plugin_info) {
    // Only load if active
    if (in_array($plugin_info['key'], $active_plugins, true)) {
      include_once $plugin_info['file'];
      error_log(
        'secondary-plugin-dir: Loaded plugin from custom path: ' . $plugin_info['slug'] . '/' . basename(
          $plugin_info['file']
        )
      );
    }
  }
}, 1);

/**
 * Register custom plugin directory URL handling
 * This allows plugins_url() to return correct URLs for our custom plugins
 */
\add_filter('plugins_url', function ($url, $path, $plugin) {
  // if its not one of our plugins just return the original url
  // array_key_exists('SFS', $_SERVER) or constant IONOS_IS_STRETCH_SFS is required to work in local wp-env
  if (! str_starts_with($plugin, IONOS_CUSTOM_PLUGINS_DIR) && ! defined('IONOS_IS_STRETCH_SFS')) {
    return $url;
  }

  // if we run in stretch sfs : replace the standard plugins URL part with sfs stretch mapping
  return str_replace('wp-content/plugins/opt/WordPress/extra', 'wp-sfsxtra', $url);
}, 10, 3);

/**
 * Show custom plugins in the admin plugins list
 * This allows users to see and treat (mostly deactivate) custom plugins like regular ones
 */
\add_filter('all_plugins', function ($plugins) {
  global $pagenow;

  switch ($pagenow) {
    case 'plugins.php':
      $custom_plugins  = get_installed_custom_plugins();
      $deleted_plugins = get_deleted_custom_plugins();

      foreach ($custom_plugins as $plugin_info) {
        // Skip deleted/hidden plugins
        if (in_array($plugin_info['key'], $deleted_plugins, true)) {
          continue;
        }

        if (! isset($plugins[$plugin_info['key']])) {
          // Get full plugin data for admin display
          $plugin_data                  = \get_plugin_data($plugin_info['file'], false, false);
          $plugins[$plugin_info['key']] = $plugin_data;
          // $plugins[$plugin_info['key']]['Description'] = $plugin_data['Description'] . ' <em>(IONOS provisioned)</em>';
        }
      }
      break;
  }

  return $plugins;
});

/**
 * Mark custom plugins as already installed in plugin installation API results
 * This shows plugins that already exist as custom plugins with an "installed" status
 */
// @TODO: improve UX: https://hosting-jira.1and1.org/browse/GPHWPP-4232
\add_filter('plugins_api_result', function ($result, $action, $args) {
  if ($action !== 'query_plugins' && $action !== 'plugin_information') {
    return $result;
  }

  $custom_plugins                = get_installed_custom_plugins();
  $custom_installed_plugin_slugs = array_column($custom_plugins, 'slug');
  $custom_installed_plugin_slugs = array_merge(
    array_map(fn ($slug) => basename(dirname($slug)), get_deleted_custom_plugins()),
    $custom_installed_plugin_slugs
  );

  if ($action === 'query_plugins' && isset($result->plugins)) {
    // Mark custom plugins as already installed
    foreach ($result->plugins as &$plugin) {
      $plugin_slug = is_object($plugin) ? $plugin->slug : ($plugin['slug'] ?? '');
      if (in_array($plugin_slug, $custom_installed_plugin_slugs, true)) {
        // Mark as installed
        if (is_object($plugin)) {
          $plugin->installed = true;
        } else {
          $plugin['installed'] = true;
        }
      }
    }
    unset($plugin); // Break reference
  }

  if ($action === 'plugin_information' && isset($result->slug)) {
    // If user tries to view details of a custom plugin, show notice
    if (in_array($result->slug, $custom_installed_plugin_slugs, true)) {
      // $result->sections['description'] = '<div class="notice notice-info"><p><strong>This plugin is already provisioned by IONOS Core and cannot be installed from WordPress.org.</strong></p></div>' . ($result->sections['description'] ?? '');
      $result->download_link = '';
    }
  }

  return $result;
}, 10, 3);

/**
 * Block installation attempts of custom plugins
 */
\add_filter('upgrader_pre_install', function ($response, $hook_extra) {
  if (isset($hook_extra['plugin'])) {
    $custom_plugins = get_installed_custom_plugins();
    $custom_slugs   = array_column($custom_plugins, 'slug');

    // Extract slug from plugin path
    $plugin_slug = dirname($hook_extra['plugin']);
    if ($plugin_slug === '.') {
      $plugin_slug = basename($hook_extra['plugin'], '.php');
    }

    if (in_array($plugin_slug, $custom_slugs, true)) {
      return new \WP_Error(
        'plugin_already_provisioned',
        // @TODO: real UX
        'This plugin is already provisioned by your WordPress Hosting and cannot be installed by upload.'
      );
    }
  }

  return $response;
}, 10, 2);

/**
 * Filter active plugins to include custom active plugins
 * This ensures that WordPress recognizes our custom active plugins as active
 */
\add_filter(
  hook_name: 'option_active_plugins',
  callback: function (array $active_plugins) {
    $custom_active = get_active_custom_plugins();
    // merge our custom active plugins with the standard active plugins
    // use array_unique to avoid duplicates since this fillter will be called multiple times
    return array_unique(array_merge($active_plugins, $custom_active));
  },
);

/**
 * prevent pollution of active_plugins with our custom plugins
 * This ensures that when WordPress updates the active_plugins option,
 * our custom plugins are not added to the standard active plugins list
 */
\add_filter(
  hook_name: 'pre_update_option_active_plugins',
  callback : function (array $value): array {
    $custom_active_plugins = get_active_custom_plugins();
    // remove our custom active plugins from the new value
    $x = array_diff($value, $custom_active_plugins);
    return $x;
  },
);

/**
 * Suppress "Plugin file does not exist" errors for custom plugins.
 * This happens because WordPress checks for plugin file existence during activation,
 * but our custom plugins are loaded directly and may not exist as separate files.
 */
\add_filter(
  hook_name: 'wp_admin_notice_markup',
  callback: function (string $markup, string $message, array $args) {

    // Check if it's an error about a missing plugin file
    // and if it relates to one of our custom plugins
    if (
      ($args['type'] === 'error' ||
        (is_array($args['additional_classes']) && in_array('error', $args['additional_classes']))) &&
      // we need to look for the localized version of the string
      str_contains($message, __('Plugin file does not exist.'))
    ) {
      // Check if the message relates to one of our activated custom plugins
      foreach (get_active_custom_plugins() as $plugin_string) {
        if (str_contains($message, $plugin_string)) {
          return ''; // suppress the error message
        }
      }
    }

    return $markup;
  },
  accepted_args : 3,
);

/**
 * Handle activation/deactivation/deletion/installation of custom plugins
 * This allows users to activate/deactivate/delete/installation of custom plugins from the admin interface
 */
\add_action('admin_init', function () {
  // Handle single activation
  if (isset($_GET['action'], $_GET['plugin']) && $_GET['action'] === 'activate') {
    $plugin = \wp_unslash($_GET['plugin']);
    if (str_starts_with($plugin, IONOS_CUSTOM_PLUGINS_PATH)) {
      \check_admin_referer('activate-plugin_' . $plugin);
      activate_custom_plugin($plugin);
      \wp_redirect(\admin_url('plugins.php?activate=true'));
      exit;
    }
  }

  // Handle bulk activation
  if (isset($_POST['action'], $_POST['checked']) && $_POST['action'] === 'activate-selected') {
    check_admin_referer('bulk-plugins');
    $plugins = array_map('\wp_unslash', $_POST['checked']);
    foreach ($plugins as $plugin) {
      if (str_starts_with($plugin, IONOS_CUSTOM_PLUGINS_PATH)) {
        activate_custom_plugin($plugin);
      }
    }
  }

  // Handle bulk deletion
  if (isset($_POST['action'], $_POST['checked']) && $_POST['action'] === 'delete-selected') {
    check_admin_referer('bulk-plugins');
    $plugins = array_map('\wp_unslash', $_POST['checked']);
    foreach ($plugins as $plugin) {
      if (str_starts_with($plugin, IONOS_CUSTOM_PLUGINS_PATH)) {
        mark_custom_plugin_as_deleted($plugin);
      }
    }
  }

  // Handle deactivation
  if (isset($_GET['action'], $_GET['plugin']) && $_GET['action'] === 'deactivate') {
    $plugin = \wp_unslash($_GET['plugin']);
    if (str_starts_with($plugin, IONOS_CUSTOM_PLUGINS_PATH)) {
      \check_admin_referer('deactivate-plugin_' . $plugin);
      deactivate_custom_plugin($plugin);
      \wp_redirect(\admin_url('plugins.php?deactivate=true'));
      exit;
    }
  }

  // Handle bulk enable auto updates
  if (isset($_POST['action'], $_POST['checked']) && $_POST['action'] === 'enable-auto-update-selected') {
    check_admin_referer('bulk-plugins');
    $plugins          = array_map('\wp_unslash', $_POST['checked']);
    $_POST['checked'] = [];
    foreach ($plugins as $plugin) {
      if (str_starts_with($plugin, IONOS_CUSTOM_PLUGINS_PATH)) {
        continue; // skip custom plugins
      }
      $_POST['checked'][] = $plugin;
    }
  }

  // Handle bulk disable auto updates
  if (isset($_POST['action'], $_POST['checked']) && $_POST['action'] === 'disable-auto-update-selected') {
    check_admin_referer('bulk-plugins');
    $plugins          = array_map('\wp_unslash', $_POST['checked']);
    $_POST['checked'] = [];
    foreach ($plugins as $plugin) {
      if (str_starts_with($plugin, IONOS_CUSTOM_PLUGINS_PATH)) {
        continue; // skip custom plugins
      }
      $_POST['checked'][] = $plugin;
    }
  }
});

// override default plugin installation AJAX handler to handle re-enabling of deleted custom plugins
\add_action('wp_ajax_install-plugin', function ($plugin) {
  check_ajax_referer('updates');

  if (empty($_POST['slug'])) {
    \wp_send_json_error(
      [
        'slug'         => '',
        'errorCode'    => 'no_plugin_specified',
        'errorMessage' => \__('No plugin specified.'),
      ]
    );
  }

  $status = [
    'install' => 'plugin',
    'slug'    => \sanitize_key(\wp_unslash($_POST['slug'])),
  ];

  if (! \current_user_can('install_plugins')) {
    $status['errorMessage'] = \__('Sorry, you are not allowed to install plugins on this site.');
    \wp_send_json_error($status);
  }

  if (! \function_exists('plugins_api')) {
    require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
  }

  $api = \plugins_api(
    'plugin_information',
    [
      'slug'   => \sanitize_key(\wp_unslash($_POST['slug'])),
      'fields' => [
        'sections' => false,
      ],
    ]
  );

  if (\is_wp_error($api)) {
    $status['errorMessage'] = $api->get_error_message();
    \wp_send_json_error($status);
  }

  $deleted_custom_plugins = get_deleted_custom_plugins();
  foreach ($deleted_custom_plugins as $deleted_custom_plugin) {
    $deleted_custom_plugin_slug = basename(dirname($deleted_custom_plugin));
    if ($deleted_custom_plugin_slug === $status['slug']) {
      unmark_custom_plugin_as_deleted($deleted_custom_plugin);

      $status['pluginName'] = is_object($api) ? $api->name : $api['name'];
      $pagenow              = isset($_POST['pagenow']) ? \sanitize_key($_POST['pagenow']) : '';
      // If installation request is coming from import page, do not return network activation link.
      $plugins_url = ('import' === $pagenow) ? \admin_url('plugins.php') : \network_admin_url('plugins.php');
      if (\current_user_can('activate_plugin', $status['slug']) && \is_plugin_inactive($status['slug'])) {
        $status['activateUrl'] = \add_query_arg(
          [
            '_wpnonce' => \wp_create_nonce('activate-plugin_' . $status['slug']),
            'action'   => 'activate',
            'plugin'   => $status['slug'],
          ],
          $plugins_url
        );
      }

      if (\is_multisite() && \current_user_can('manage_network_plugins') && 'import' !== $pagenow) {
        $status['activateUrl'] = \add_query_arg([
          'networkwide' => 1,
        ], $status['activateUrl']);
      }

      \wp_send_json_success($status);
    }
  }
}, 0);

/*
  when a plugin gets installed, this hooks will be called to check for dependencies
  we override it to handle our custom plugins.
  this hook will be called when a plugin installation is requested via AJAX
 */
\add_action('wp_ajax_check_plugin_dependencies', function () {
  check_ajax_referer('updates');

  if (empty($_POST['slug'])) {
    \wp_send_json_error(
      [
        'slug'         => '',
        'pluginName'   => '',
        'errorCode'    => 'no_plugin_specified',
        'errorMessage' => \__('No plugin specified.'),
      ]
    );
  }

  $slug   = \sanitize_key(\wp_unslash($_POST['slug']));
  $status = [
    'slug' => $slug,
  ];

  $custom_plugin = array_find(get_installed_custom_plugins(), fn ($_) => $_['slug'] === $slug);

  if ($custom_plugin===null) {
    // not a custom plugin, fallback to default handler
    return;
  }

  $status['pluginName'] = $custom_plugin['data']['Name'];
  $status['plugin']     = $custom_plugin['key'];

  if (\current_user_can('activate_plugin', $custom_plugin['slug']) && \is_plugin_inactive(
    $custom_plugin['slug']
  )) {
    $status['activateUrl'] = \add_query_arg(
      [
        '_wpnonce' => \wp_create_nonce('activate-plugin_' . $custom_plugin['key']),
        'action'   => 'activate',
        'plugin'   => $custom_plugin['key'],
      ],
      \is_multisite() ? \network_admin_url('plugins.php') : \admin_url('plugins.php')
    );
  }

  if (\is_multisite() && \current_user_can('manage_network_plugins')) {
    $status['activateUrl'] = \add_query_arg([
      'networkwide' => 1,
    ], $status['activateUrl']);
  }

  // @TODO: check for dependencies of the custom plugin if required

  $status['message'] = \__('All required plugins are installed and activated.');
  \wp_send_json_success($status);
});

/*
  when a plugin gets activated using ajay this hook will be called
  we override it to handle our custom plugins.
*/
\add_action('wp_ajax_activate_plugin', function () {
  check_ajax_referer('updates');

  if (empty($_POST['name']) || empty($_POST['slug']) || empty($_POST['plugin'])) {
    \wp_send_json_error(
      [
        'slug'         => '',
        'pluginName'   => '',
        'plugin'       => '',
        'errorCode'    => 'no_plugin_specified',
        'errorMessage' => \__('No plugin specified.'),
      ]
    );
  }

  $slug = \wp_unslash($_POST['slug']);

  $custom_plugin = array_find(get_installed_custom_plugins(), fn ($_) => $_['slug'] === $slug);

  if ($custom_plugin===null) {
    // not a custom plugin, fallback to default handler
    return;
  }

  $status = [
    'activate'   => 'plugin',
    'slug'       => $slug,
    'pluginName' => \wp_unslash($_POST['name']),
    'plugin'     => \wp_unslash($_POST['plugin']),
  ];

  if (! \current_user_can('activate_plugin', $status['plugin'])) {
    $status['errorMessage'] = \__('Sorry, you are not allowed to activate plugins on this site.');
    \wp_send_json_error($status);
  }

  if (is_custom_plugin_active($custom_plugin['key'])) {
    $status['errorMessage'] = sprintf(
      /* translators: %s: Plugin name. */
      \__('%s is already active.'),
      $status['pluginName']
    );
  }

  activate_custom_plugin($custom_plugin['key']);

  \wp_send_json_success($status);
}, 0);

/*
  WordPress calls 'delete_plugin' action when a plugin is deleted from the plugins list
  This hook works for both single and bulk deletions.
 */
\add_action('delete_plugin', function ($plugin_key) {
  if (str_starts_with($plugin_key, IONOS_CUSTOM_PLUGINS_PATH)) {
    // If this is an AJAX request, send JSON response
    if (\wp_doing_ajax()) {
      $plugin_file = IONOS_CUSTOM_DIR . '/' . $plugin_key;
      if (! file_exists($plugin_file)) {
        return;
      }

      // Check if it's a valid plugin file by looking for plugin headers
      $plugin_data = \get_file_data($plugin_file, [
        'Name' => 'Plugin Name',
      ]);
      mark_custom_plugin_as_deleted($plugin_key);

      \wp_send_json_success([
        'delete'     => 'plugin',
        'plugin'     => $plugin_key,
        'slug'       => $plugin_key,
        'pluginName' => $plugin_data['Name'],
      ]);
    }
  }
});

/*
  Modify plugin installation action links for custom plugins. This changes
  - the "Install Now" button to "Activate" or disabled "active" button for installed custom plugins
  - the "Install Now" button to reenable a deleted custom plugin
*/
\add_filter('plugin_install_action_links', function ($links, $plugin) {
  $custom_plugin = array_find(
    get_installed_custom_plugins(),
    // CAVEAT: we cannot name it $custom plugin since rector will name it also $custom_plugin_*
    // fn ($custom_plugin) => $custom_plugin['slug'] === $plugin['slug'],
    fn ($_) => $_['slug'] === $plugin['slug'],
  );

  $is_active                  = false;
  $deleted_custom_plugin_slug = null;

  if (is_array($custom_plugin)) {
    $is_active = is_custom_plugin_active($custom_plugin['key']);
  } else {
    $deleted_custom_plugin_slug = array_find(
      get_deleted_custom_plugins(),
      fn ($_) => str_contains($_, $plugin['slug']),
    );
  }

  // abort if not a available or deleted custom plugin
  if ($custom_plugin === null && $deleted_custom_plugin_slug === null) {
    return $links;
  }

  // search for install link and replace it with activate link
  foreach ($links as $key => &$link) {
    if (str_contains($link, 'install-now')) {
      if ($deleted_custom_plugin_slug !== null) {
        /*
          replace install link url with link to custom plugin if
          plugin exists as disabled custom plugin
        */
        // "<a class="install-now button" data-slug="extendify" href="http://localhost:8888/wp-admin/update.php?action=install-plugin&#038;plugin=extendify&#038;_wpnonce=f4bd11d090" aria-label="Install Extendify 2.3.1 now" data-name="Extendify 2.3.1" role="button">Install Now</a>"
        $link = str_replace(
          'plugin=' . \esc_attr($plugin['slug']),
          'plugin=' . \esc_attr($deleted_custom_plugin_slug),
          $link
        );

        break;
      }

      if ($is_active) {
        /*
          replace install link with disabled "active" link button for custom plugins if
          a plugin which is already provisioned as custom plugin is already active
        */
        $button = sprintf(
          '<button type="button" class="button button-disabled" disabled="disabled">%s</button>',
          \_x('Active', 'plugin')
        );
        $link = $button;
        break;
      }

      if ($custom_plugin !== null) {
        /*
          replace install link with activate link for custom plugins if user wants
          to install a plugin which is already provisioned as custom plugin
        */

        // code borrowed and adapted from WP core (wp-admin/includes/class-wp-plugins-list-table.php)
        $button_text  = \_x('Activate', 'plugin');
        $button_label = \_x('Activate %s', 'plugin');
        $activate_url = \add_query_arg(
          [
            '_wpnonce' => \wp_create_nonce('activate-plugin_' . $custom_plugin['key']),
            'action'   => 'activate',
            'plugin'   => $custom_plugin['key'],
          ],
          \network_admin_url('plugins.php')
        );

        if (\is_network_admin()) {
          $button_text  = \_x('Network Activate', 'plugin');
          $button_label = \_x('Network Activate %s', 'plugin');
          $activate_url = \add_query_arg([
            'networkwide' => 1,
          ], $activate_url);
        }

        $button = sprintf(
          '<a href="%1$s" data-name="%2$s" data-slug="%3$s" data-plugin="%4$s" class="button button-primary activate-now" aria-label="%5$s" role="button">%6$s</a>',
          \esc_url($activate_url),
          \esc_attr($custom_plugin['data']['Name']),
          \esc_attr($custom_plugin['slug']),
          \esc_attr($custom_plugin['key']),
          \esc_attr(sprintf($button_label, $custom_plugin['data']['Name'])),
          $button_text
        );

        $link = $button;
        break;
      }
    }
  }

  return $links;
}, 10, 2);

/*
  when a plugin gets deactivated using bulk action this hook will be called
  we override it to handle our custom plugins.
*/
\add_action('deactivate_plugin', function ($plugin, $network_deactivating) {
  if (str_starts_with($plugin, IONOS_CUSTOM_PLUGINS_PATH)) {
    deactivate_custom_plugin($plugin);
  }
}, 10, 2);

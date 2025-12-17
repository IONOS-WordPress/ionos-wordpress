<?php

namespace ionos\stretch_extra\secondary_plugin_dir;

use const ionos\stretch_extra\IONOS_CUSTOM_DIR;

defined('ABSPATH') || exit();

// Define custom plugins directory
const IONOS_CUSTOM_PLUGINS_PATH = 'plugins/';
const IONOS_CUSTOM_PLUGINS_DIR  = IONOS_CUSTOM_DIR . '/' . IONOS_CUSTOM_PLUGINS_PATH;

// Option name to store active custom plugins
const IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION = 'IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION';

// @TODO: hack just for beta
\add_action('plugins_loaded', function () {
  // \delete_option(IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION);
  $is_initialized = \get_option(IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION);
  if($is_initialized !== false) {
    return;
  }

  \update_option('extendify_insights_stop', true, true);

  // Initialize the active plugins option as an empty array
  foreach(get_custom_plugins() as $plugin_info) {
      // Activate all custom plugins by default on first run
      activate_custom_plugin($plugin_info['key']);
  }
});

/**
 * Get list of active custom plugins
 */
function get_active_custom_plugins()
{
  return \get_option(IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION, []);
}

/**
 * Check if a custom plugin is active
 */
function is_custom_plugin_active($plugin_key)
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
 * Returns an array of plugin info: ['key' => plugin_key, 'file' => plugin_file, 'data' => plugin_data]
 */
function get_custom_plugins(): array
{
  static $custom_plugins = null;

  if ($custom_plugins !== null) {
    return $custom_plugins;
  }

  $custom_plugins = [];

  if (! is_dir(IONOS_CUSTOM_PLUGINS_DIR)) {
    error_log(
      sprintf(
        'secondary-plugin-dir: skip loading plugins from custom directory(=%s) - directory does not exist or no valid plugins found',
        IONOS_CUSTOM_PLUGINS_DIR,
      )
    );

    return $custom_plugins;
  }

  $plugin_dirs = glob(IONOS_CUSTOM_PLUGINS_DIR . '*', GLOB_ONLYDIR);

  foreach ($plugin_dirs as $plugin_dir) {
    $plugin_slug  = basename($plugin_dir);
    $plugin_files = glob($plugin_dir . '/*.php');

    foreach ($plugin_files as $plugin_file) {
      if (file_exists($plugin_file)) {
        // Check if it's a valid plugin file by looking for plugin headers
        $plugin_data = \get_file_data($plugin_file, [
          'Name' => 'Plugin Name',
        ]);
        if (! empty($plugin_data['Name'])) {
          $plugin_key       = IONOS_CUSTOM_PLUGINS_PATH . $plugin_slug . '/' . basename($plugin_file);
          $custom_plugins[] = [
            'key'  => $plugin_key,
            'file' => $plugin_file,
            'slug' => $plugin_slug,
            'data' => $plugin_data,
          ];
          break; // Only process one main plugin file per directory
        }
      }
    }
  }

  return $custom_plugins;
}

/**
 * Inject activated custom plugins
 */
\add_action('plugins_loaded', function () {
  $custom_plugins = get_custom_plugins();
  $active_plugins = get_active_custom_plugins();

  foreach ($custom_plugins as $plugin_info) {
    // Only load if active
    if (in_array($plugin_info['key'], $active_plugins, true)) {
      include_once $plugin_info['file'];
      error_log(
        'secondary-plugin-dir: Loaded plugin from custom path: ' . $plugin_info['slug'] . '/' . basename($plugin_info['file'])
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
  // array_key_exists('SFS', $_SERVER) is required to work in local wp-env
  if (!str_starts_with($plugin, IONOS_CUSTOM_PLUGINS_DIR) && !array_key_exists('SFS', $_SERVER)) {
    return $url;
  }

  // if we run in stretch sfs : replace the standard plugins URL part with sfs stretch mapping
  return str_replace("wp-content/plugins/opt/WordPress/extra", "wp-sfsxtra", $url);
}, 10, 3);

/**
 * Show custom plugins in the admin plugins list
 * This allows users to see and treat (mostly deactivate) custom plugins like regular ones
 */
\add_filter('all_plugins', function ($plugins) {
  global $pagenow;

  switch ($pagenow) {
    case 'plugins.php':
      $custom_plugins = get_custom_plugins();

      foreach ($custom_plugins as $plugin_info) {
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
 * Prevent installation of plugins that already exist as custom plugins
 * This filters the plugin installation API results to hide plugins that are already available
 */
\add_filter('plugins_api_result', function ($result, $action, $args) {
  if ($action !== 'query_plugins' && $action !== 'plugin_information') {
    return $result;
  }

  $custom_plugins = get_custom_plugins();
  $custom_slugs   = array_column($custom_plugins, 'slug');

  if ($action === 'query_plugins' && isset($result->plugins)) {
    // Filter out plugins that match our custom plugin slugs
    $result->plugins = array_filter($result->plugins, function ($plugin) use ($custom_slugs) {
      $plugin_slug = is_object($plugin) ? $plugin->slug : ($plugin['slug'] ?? '');
      return ! in_array($plugin_slug, $custom_slugs, true);
    });
    $result->plugins = array_values($result->plugins); // Re-index array
  }

  if ($action === 'plugin_information' && isset($result->slug)) {
    // If user tries to view details of a custom plugin, show notice
    if (in_array($result->slug, $custom_slugs, true)) {
      $result->sections['description'] = '<div class="notice notice-info"><p><strong>This plugin is already provisioned by IONOS Core and cannot be installed from WordPress.org.</strong></p></div>' . ($result->sections['description'] ?? '');
      // Remove installation-related data
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
    $custom_plugins = get_custom_plugins();
    $custom_slugs   = array_column($custom_plugins, 'slug');

    // Extract slug from plugin path
    $plugin_slug = dirname($hook_extra['plugin']);
    if ($plugin_slug === '.') {
      $plugin_slug = basename($hook_extra['plugin'], '.php');
    }

    if (in_array($plugin_slug, $custom_slugs, true)) {
      return new \WP_Error(
        'plugin_already_provisioned',
        'This plugin is already provisioned by IONOS Core and cannot be installed from WordPress.org.'
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
  accepted_args : 1,
);

/**
 * prevent pollution of active_plugins with our custom plugins
 * This ensures that when WordPress updates the active_plugins option,
 * our custom plugins are not added to the standard active plugins list
 */
\add_filter(
  hook_name: 'pre_update_option_active_plugins',
  callback : function (array $value, array $old_value, string $option): array {
    $custom_active_plugins = get_active_custom_plugins();
    // remove our custom active plugins from the new value
    $x = array_diff($value, $custom_active_plugins);
    return $x;
  },
  accepted_args : 3,
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
 * Handle activation/deactivation of custom plugins
 * This allows users to activate/deactivate custom plugins from the admin interface
 */
\add_action('admin_init', function () {
  // Handle activation
  if (isset($_GET['action']) && $_GET['action'] === 'activate' && isset($_GET['plugin'])) {
    $plugin = \wp_unslash($_GET['plugin']);
    if (str_starts_with($plugin, IONOS_CUSTOM_PLUGINS_PATH)) {
      \check_admin_referer('activate-plugin_' . $plugin);
      activate_custom_plugin($plugin);
      \wp_redirect(\admin_url('plugins.php?activate=true'));
      exit;
    }
  }

  // Handle deactivation
  if (isset($_GET['action']) && $_GET['action'] === 'deactivate' && isset($_GET['plugin'])) {
    $plugin = \wp_unslash($_GET['plugin']);
    if (str_starts_with($plugin, IONOS_CUSTOM_PLUGINS_PATH)) {
      \check_admin_referer('deactivate-plugin_' . $plugin);
      deactivate_custom_plugin($plugin);
      \wp_redirect(\admin_url('plugins.php?deactivate=true'));
      exit;
    }
  }
});

/**
 * Prevent deletion of custom plugins
 * These plugins are loaded directly and should not be deleted by users
 */
\add_filter('plugin_action_links', function ($actions, $plugin_file) {
  if (str_starts_with($plugin_file, IONOS_CUSTOM_PLUGINS_PATH)) {
    // Remove delete link since they're custom loaded plugins
    unset($actions['delete']);
    $actions['must_use'] = '<span style="color: #999;">ionos-core provisioned plugins cannot be deleted.</span>';
  }
  return $actions;
}, 10, 2);

/**
 * Bypass plugin file existence check during activation
 * This prevents errors when activating custom plugins since they are loaded directly
 */
\add_action('admin_init', function () {
  if (isset($_GET['action']) && $_GET['action'] === 'activate' && isset($_GET['plugin'])) {
    $plugin = \wp_unslash($_GET['plugin']);
    if (str_starts_with($plugin, IONOS_CUSTOM_PLUGINS_PATH)) {
      // Redirect back without error since plugin is already loaded
      \wp_redirect(\admin_url('plugins.php?activate=true'));
      exit;
    }
  }
});

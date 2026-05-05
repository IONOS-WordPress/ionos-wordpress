<?php

defined('ABSPATH') || exit();

/**
 * 0. THE GENERIC SILENCER
 * Deletes warnings for virtual paths before they hit the screen.
 */
set_error_handler(function ($errno, $errstr) {
  if ($errno === E_WARNING) {
    if (
      strpos($errstr, '01-ext-')                !== false ||
      strpos($errstr, 'extendify')              !== false ||
      strpos($errstr, 'ionos-essentials')       !== false ||
      strpos($errstr, 'wp-content/plugins/01-') !== false
    ) {
      return true;
    }
  }
  return false;
});

/**
 * 1. THE PRE-EMPTIVE STRIKE
 * Satisfies all modern WP header keys to kill "Undefined array key" warnings.
 */
add_filter('pre_get_plugin_data', function ($data, $plugin_file) {
  if (
    strpos($plugin_file, '01-ext-')          !== false ||
    strpos($plugin_file, 'extendify')        !== false ||
    strpos($plugin_file, 'ionos-essentials') !== false
  ) {
    $name = basename(dirname($plugin_file));
    if ($name === 'plugins' || $name === '.') {
      $name = basename($plugin_file, '.php');
    }

    return [
      'Name'        => $name,
      'Title'       => $name,
      'Description' => 'IONOS Stretch Asset (Virtual)',
      'Author'      => 'IONOS',
      'AuthorName'  => 'IONOS',
      'Version'     => '1.0.0',
      'TextDomain'  => $name,
      'DomainPath'  => '',
      'PluginURI'   => '',
      'AuthorURI'   => '',
      'Network'     => false,
      'RequiresWP'  => '',
      'RequiresPHP' => '',
      'UpdateURI'   => '',
    ];
  }
  return $data;
}, 1, 2);

/**
 * 2. GLOBAL PATH CORRECTOR
 * Maps clean slugs back to the real IONOS directory.
 * PRIORITY 1: Must run before WP checks for file existence.
 */
add_filter('plugin_file_path', function ($path, $plugin) {
  $helper_path = dirname(__DIR__) . '/secondary-plugin-dir.php';
  if (! file_exists($helper_path)) {
    return $path;
  }

  @require_once $helper_path;
  $ns = '\ionos\stretch_extra\secondary_plugin_dir\\';

  if (function_exists($ns . 'get_all_custom_plugins')) {
    $all = ($ns . 'get_all_custom_plugins')();
    foreach ($all as $entry) {
      // Create the same 'clean slug' WordPress is checking for
      $slug = str_replace('plugins/', '', $entry['key']);

      if ($slug === $plugin || $entry['key'] === $plugin) {
        return $entry['file'];
      }
    }
  }
  return $path;
}, 1, 2);

/**
 * 3. THE STATUS BRIDGE & SILENT PREVENTER
 * Synchronizes the UI and prevents WP from deactivating "missing" files.
 */
add_filter('option_active_plugins', function ($active_plugins) {
  $helper_path = dirname(__DIR__) . '/secondary-plugin-dir.php';
  if (! file_exists($helper_path)) {
    return $active_plugins;
  }

  @require_once $helper_path;
  $ns = '\ionos\stretch_extra\secondary_plugin_dir\\';

  if (! function_exists($ns . 'get_active_custom_plugins')) {
    return $active_plugins;
  }

  $custom_active  = ($ns . 'get_active_custom_plugins')();
  $all_custom     = ($ns . 'get_all_custom_plugins')();
  $active_plugins = is_array($active_plugins) ? $active_plugins : [];

  $custom_slug_map = [];
  foreach ($all_custom as $entry) {
    $custom_slug_map[] = str_replace('plugins/', '', $entry['key']);
  }

  // Sync logic
  $active_plugins = array_filter($active_plugins, function ($plugin) use ($custom_slug_map, $custom_active) {
    if (! in_array($plugin, $custom_slug_map)) {
      return true;
    }
    return in_array('plugins/' . $plugin, $custom_active);
  });

  foreach ($custom_active as $full_key) {
    $plugin_key = str_replace('plugins/', '', $full_key);
    if (! in_array($plugin_key, $active_plugins)) {
      $active_plugins[] = $plugin_key;
    }
  }

  // CRITICAL: Prevent WordPress from "cleaning" these plugins out of the list
  // if it thinks the files are missing during the load process.
  return array_values(array_unique($active_plugins));
}, 1);

/**
 * 4. THE LIST CLEANER
 */
add_filter('all_plugins', function ($plugins) {
  $helper_path = dirname(__DIR__) . '/secondary-plugin-dir.php';
  if (! file_exists($helper_path)) {
    return $plugins;
  }

  @require_once $helper_path;
  $ns = '\ionos\stretch_extra\secondary_plugin_dir\\';
  if (! function_exists($ns . 'get_all_custom_plugins')) {
    return $plugins;
  }

  $mounted = ($ns . 'get_all_custom_plugins')();
  foreach ($mounted as $entry) {
    if (function_exists($ns . 'is_custom_plugin_deleted') && ($ns . 'is_custom_plugin_deleted')($entry['key'])) {
      continue;
    }

    $slug = str_replace('plugins/', '', $entry['key']);
    unset($plugins[$entry['file']], $plugins[$entry['key']]);

    $plugins[$slug] = [
      'Name'        => $entry['data']['Name'] ?? $slug,
      'Version'     => $entry['version']      ?? '1.0.0',
      'Description' => 'IONOS Stretch Asset',
      'Author'      => 'IONOS',
      'Title'       => $entry['data']['Name'] ?? $slug,
      'TextDomain'  => $slug,
      'DomainPath'  => '',
      'PluginURI'   => '',
      'AuthorURI'   => '',
      'Network'     => false,
    ];
  }
  return $plugins;
}, 999);

/**
 * 5. WP-CLI HIJACK (Updated for Passive Install)
 */
if (defined('WP_CLI') && WP_CLI) {
  WP_CLI::add_hook('before_invoke:plugin', function () {
    $runner     = WP_CLI::get_runner();
    $subcommand = $runner->arguments[1] ?? '';
    $user_slug  = $runner->arguments[2] ?? '';
    $assoc_args = $runner->assoc_args;

    if (! in_array($subcommand, ['activate', 'deactivate', 'delete', 'install'])) {
      return;
    }

    $helper_path = dirname(__DIR__) . '/secondary-plugin-dir.php';
    if (! file_exists($helper_path)) {
      return;
    }
    @require_once $helper_path;
    $ns = '\ionos\stretch_extra\secondary_plugin_dir\\';

    foreach (($ns . 'get_all_custom_plugins')() as $entry) {
      $full_key  = $entry['key'];
      $clean_key = str_replace('plugins/', '', $full_key);
      $dir_slug  = dirname($clean_key);

      if ($user_slug === $entry['slug'] || $user_slug === $dir_slug || $user_slug === $clean_key) {

        switch ($subcommand) {
          case 'install':
            // 1. "Restore" it to the user's eye if it was deleted
            ($ns . 'unmark_custom_plugin_as_deleted')($full_key);
            WP_CLI::log("Mounted plugin '{$user_slug}' verified and restored to list.");

            // 2. If they DID provide --activate, turn it on
            if (isset($assoc_args['activate'])) {
              ($ns . 'activate_custom_plugin')($full_key);
              WP_CLI::log('Activating...');
            } else {
              // Just a friendly notice that it's ready but not active
              WP_CLI::log("Plugin is now available in the list. Run 'wp plugin activate {$user_slug}' to enable.");
            }
            break;

          case 'activate':
            ($ns . 'activate_custom_plugin')($full_key);
            break;

          case 'deactivate':
            ($ns . 'deactivate_custom_plugin')($full_key);
            break;

          case 'delete':
            ($ns . 'mark_custom_plugin_as_deleted')($full_key);
            break;
        }

        // Cache Flushing logic
        wp_cache_delete('alloptions', 'options');
        wp_cache_delete('active_plugins', 'options');
        wp_cache_delete('IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION', 'options');
        wp_cache_delete('IONOS_CUSTOM_DELETED_PLUGINS_OPTION', 'options');

        if (function_exists('apcu_clear_cache')) {
          apcu_clear_cache();
        }

        WP_CLI::success("Successfully handled {$user_slug}.");
        exit;
      }
    }
  });
}

/**
 * DEBUG: Plugin Status Monitor
 */
add_action('admin_notices', function () {
  if (! current_user_can('manage_options')) {
    return;
  }
  $helper_path = dirname(__DIR__) . '/secondary-plugin-dir.php';
  if (! file_exists($helper_path)) {
    return;
  }
  @require_once $helper_path;
  $ns = '\ionos\stretch_extra\secondary_plugin_dir\\';

  $db_active = ($ns . 'get_active_custom_plugins')();
  $wp_active = get_option('active_plugins', []);

  echo '<div class="notice notice-info is-dismissible" style="border-left-color: #007cba;">';
  echo '<h3>🔍 IONOS Plugin Debugger</h3>';
  echo '<table style="text-align: left; margin-bottom: 10px;">';
  echo '<thead><tr><th>Plugin Slug</th><th>IONOS DB Status</th><th>WP Global Array</th></tr></thead><tbody>';

  foreach (($ns . 'get_all_custom_plugins')() as $entry) {
    $ui_slug  = str_replace('plugins/', '', $entry['key']);
    $in_ionos = in_array($entry['key'], $db_active) ? '✅ ACTIVE' : '❌ INACTIVE';
    $in_wp    = in_array($ui_slug, $wp_active) ? '✅ FOUND' : '⚠️ MISSING';
    echo "<tr><td><strong>{$ui_slug}</strong></td><td>{$in_ionos}</td><td>{$in_wp}</td></tr>";
  }
  echo '</tbody></table></div>';
});

/**
 * BRUTE FORCE: Suppress "Plugin file does not exist" errors for custom plugins.
 * This removes the error notice from the UI entirely.
 */
\add_filter('wp_admin_notice_markup', function ($markup, $message, array $args) {
  // Check if it's an error message
  $is_error = ($args['type'] === 'error' || (isset($args['additional_classes']) && in_array(
    'error',
    $args['additional_classes']
  )));

  if ($is_error && str_contains($message, 'has been deactivated due to an error')) {
    // List of slugs to protect from showing deactivation errors
    $protected_slugs = ['extendify', 'ionos-essentials', '01-ext-'];

    foreach ($protected_slugs as $slug) {
      if (str_contains($message, $slug)) {
        return ''; // Delete the markup, making the error invisible
      }
    }
  }

  // Also catch the direct "Plugin file does not exist" translation
  if ($is_error && str_contains($message, __('Plugin file does not exist.'))) {
    return '';
  }

  return $markup;
}, 10, 3);

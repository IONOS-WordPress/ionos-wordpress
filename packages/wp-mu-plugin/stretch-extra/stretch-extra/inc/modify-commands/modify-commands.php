<?php

defined('ABSPATH') || exit();

/**
 * 0. THE GENERIC SILENCER
 * Deletes warnings for virtual paths before they hit the screen.
 */
set_error_handler(function ($errno, $errstr) {
  if ($errno === E_WARNING) {
    if (
      strpos($errstr, '01-ext-') !== false ||
      strpos($errstr, 'extendify') !== false ||
      strpos($errstr, 'ionos-essentials') !== false ||
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
    strpos($plugin_file, '01-ext-') !== false ||
    strpos($plugin_file, 'extendify') !== false ||
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
 * Maps clean slugs back to the real IONOS directory so WP finds the files.
 */
add_filter('plugin_file_path', function ($path, $plugin) {
  $helper_path = dirname(__DIR__) . '/secondary-plugin-dir.php';
  if (!file_exists($helper_path)) return $path;

  @require_once $helper_path;
  $ns = '\ionos\stretch_extra\secondary_plugin_dir\\';

  if (function_exists($ns . 'get_all_custom_plugins')) {
    $all = ($ns . 'get_all_custom_plugins')();
    foreach ($all as $entry) {
      // Create a slug to compare against the request
      $slug = str_replace('plugins/', '', $entry['key']);
      
      // If WP is looking for 'extendify/extendify.php', return the real absolute path
      if ($slug === $plugin || $entry['key'] === $plugin) {
        return $entry['file']; // Returns /opt/WordPress/extra/plugins/...
      }
    }
  }
  return $path;
}, 1, 2);

/**
 * 3. THE STATUS BRIDGE
 */
add_filter('option_active_plugins', function ($active_plugins) {
  $helper_path = dirname(__DIR__) . '/secondary-plugin-dir.php';
  if (!file_exists($helper_path)) return $active_plugins;

  @require_once $helper_path;
  $ns = '\ionos\stretch_extra\secondary_plugin_dir\\';
  
  // Get the real active list (with 'plugins/...') from the DB
  $custom_active = (function_exists($ns . 'get_active_custom_plugins')) ? ($ns . 'get_active_custom_plugins')() : [];
  $active_plugins = is_array($active_plugins) ? $active_plugins : [];

  foreach ($custom_active as $full_key) {
    $slug = str_replace('plugins/', '', $full_key);
    if (!in_array($slug, $active_plugins)) {
      $active_plugins[] = $slug; // Inject the clean slug for the UI to match
    }
  }
  return array_values(array_unique($active_plugins));
}, 20);

/**
 * 4. THE LIST CLEANER
 */
add_filter('all_plugins', function ($plugins) {
  $helper_path = dirname(__DIR__) . '/secondary-plugin-dir.php';
  if (!file_exists($helper_path)) return $plugins;

  @require_once $helper_path;
  $ns = '\ionos\stretch_extra\secondary_plugin_dir\\';
  if (!function_exists($ns . 'get_all_custom_plugins')) return $plugins;

  $mounted = ($ns . 'get_all_custom_plugins')();
  $mounted = is_array($mounted) ? $mounted : [];

  foreach ($mounted as $entry) {
    if (function_exists($ns . 'is_custom_plugin_deleted') && ($ns . 'is_custom_plugin_deleted')($entry['key'])) {
      continue;
    }

    // This is the "Clean Slug" you want to see in the UI
    $slug = str_replace('plugins/', '', $entry['key']);
    
    // Remove the original path-based entry to avoid showing "plugins/..."
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
 * 5. WP-CLI HIJACK
 */
if (defined('WP_CLI') && WP_CLI) {
  WP_CLI::add_hook('before_invoke:plugin', function () {
    $runner     = WP_CLI::get_runner();
    $subcommand = $runner->arguments[1] ?? '';
    $slug       = $runner->arguments[2] ?? '';
    $assoc_args = $runner->assoc_args;

    if (!in_array($subcommand, ['install', 'activate', 'deactivate', 'delete'])) return;

    $helper_path = dirname(__DIR__) . '/secondary-plugin-dir.php';
    if (!file_exists($helper_path)) return;

    @require_once $helper_path;
    $ns = '\ionos\stretch_extra\secondary_plugin_dir\\';

    $all = ($ns . 'get_all_custom_plugins')();
    foreach ($all as $entry) {
      $clean_key = str_replace('plugins/', '', $entry['key']);

      if ($entry['slug'] === $slug || $clean_key === $slug) {
        switch ($subcommand) {
          case 'install':
            ($ns . 'unmark_custom_plugin_as_deleted')($entry['key']); //
            if (isset($assoc_args['activate'])) ($ns . 'activate_custom_plugin')($entry['key']);
            break;
          case 'activate':
            ($ns . 'activate_custom_plugin')($entry['key']); //
            break;
          case 'deactivate':
            ($ns . 'deactivate_custom_plugin')($entry['key']); //
            break;
          case 'delete':
            ($ns . 'mark_custom_plugin_as_deleted')($entry['key']); //
            break;
        }

        // Use the option name defined in your helper
        wp_cache_delete('IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION', 'options');
        if (function_exists('wp_cache_flush')) wp_cache_flush();
        WP_CLI::success("Done with {$slug}");
        exit;
      }
    }
  });
}

<?php

defined('ABSPATH') || exit();

if (defined('WP_CLI') && WP_CLI) {

  /**
   * 1. THE PRE-EMPTIVE STRIKE
   */
  add_filter('pre_get_plugin_data', function ($data, $plugin_file) {
    if (strpos($plugin_file, '01-ext-ion8dhas7-stretch') !== false || strpos($plugin_file, 'extendify') !== false) {
      return [
        'Name'        => basename(dirname($plugin_file)),
        'Version'     => '1.0.0',
        'Description' => 'IONOS Stretch Asset (Virtual)',
        'Author'      => 'IONOS',
        'TextDomain'  => basename(dirname($plugin_file)),
      ];
    }
    return $data;
  }, 10, 2);

  /**
   * 2. THE PATH CORRECTOR
   */
  add_filter('plugin_file_path', function ($path, $plugin) {
    $helper_path = dirname(__DIR__) . '/secondary-plugin-dir.php';
    if (file_exists($helper_path)) {
      @require_once $helper_path;
      $ns = '\ionos\stretch_extra\secondary_plugin_dir\\';
      if (function_exists($ns . 'get_all_custom_plugins')) {
        $all = ($ns . 'get_all_custom_plugins')();
        foreach ($all as $entry) {
          $slug = str_replace('plugins/', '', $entry['key']);
          if ($slug === $plugin || $entry['key'] === $plugin) {
            return $entry['file'];
          }
        }
      }
    }
    return $path;
  }, 1, 2);

  /**
   * 3. THE LIST CLEANER & SLUG SYNC
   */
  add_filter('all_plugins', function ($plugins) {
    $helper_path = dirname(__DIR__) . '/secondary-plugin-dir.php';
    if (! file_exists($helper_path)) {
      return $plugins;
    }

    @require_once $helper_path;
    $ns = '\ionos\stretch_extra\secondary_plugin_dir\\';

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
      ];
    }
    return $plugins;
  }, 999);

  /**
   * 4. THE STATUS BRIDGE
   */
  add_filter('option_active_plugins', function ($active_plugins) {
    $helper_path = dirname(__DIR__) . '/secondary-plugin-dir.php';
    if (! file_exists($helper_path)) {
      return $active_plugins;
    }

    @require_once $helper_path;
    $ns = '\ionos\stretch_extra\secondary_plugin_dir\\';

    $custom_active = ($ns . 'get_active_custom_plugins')();
    foreach ($custom_active as $full_key) {
      $slug = str_replace('plugins/', '', $full_key);
      if (! in_array($slug, $active_plugins)) {
        $active_plugins[] = $slug;
      }
    }
    return $active_plugins;
  }, 20);

  /**
   * 5. COMMAND HIJACK (With support for --activate flag)
   */
  WP_CLI::add_hook('before_invoke:plugin', function () {
    $runner     = WP_CLI::get_runner();
    $subcommand = $runner->arguments[1] ?? '';
    $slug       = $runner->arguments[2] ?? '';
    $assoc_args = $runner->assoc_args;

    if (! in_array($subcommand, ['install', 'activate', 'deactivate', 'delete'])) {
      return;
    }

    $helper_path = dirname(__DIR__) . '/secondary-plugin-dir.php';
    if (! file_exists($helper_path)) {
      return;
    }

    @require_once $helper_path;
    $ns = '\ionos\stretch_extra\secondary_plugin_dir\\';

    $all = ($ns . 'get_all_custom_plugins')();
    foreach ($all as $entry) {
      $clean_key = str_replace('plugins/', '', $entry['key']);

      if ($entry['slug'] === $slug || $clean_key === $slug) {
        switch ($subcommand) {
          case 'install':
            ($ns . 'unmark_custom_plugin_as_deleted')($entry['key']);
            WP_CLI::log("Installed: {$slug}");

            // Handle the --activate flag inside install
            if (isset($assoc_args['activate'])) {
              ($ns . 'activate_custom_plugin')($entry['key']);
              WP_CLI::log("Activated: {$slug}");
            }
            break;

          case 'activate':
            ($ns . 'activate_custom_plugin')($entry['key']);
            WP_CLI::log("Activated: {$slug}");
            break;

          case 'deactivate':
            ($ns . 'deactivate_custom_plugin')($entry['key']);
            WP_CLI::log("Deactivated: {$slug}");
            break;

          case 'delete':
            ($ns . 'mark_custom_plugin_as_deleted')($entry['key']);
            WP_CLI::log("Deleted: {$slug}");
            break;
        }

        // Flush and exit
        wp_cache_delete('IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION', 'options');
        if (function_exists('wp_cache_flush')) {
          wp_cache_flush();
        }

        WP_CLI::success("Done with {$slug}");
        exit;
      }
    }
  });
}

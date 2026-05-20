<?php

namespace ionos\stretch_extra\secondary_theme_dir;

use WP_CLI;

if (! defined('WP_CLI') || ! WP_CLI) {
  return;
}

if (! defined('IONOS_CUSTOM_DIR')) {
  define('IONOS_CUSTOM_DIR', dirname(__DIR__, 2));
}

$custom_theme_path = IONOS_CUSTOM_DIR . '/themes';

/**
 * Register BOTH directories so WordPress can find custom and core themes together.
 */
if (defined('ABSPATH')) {
  \register_theme_directory(ABSPATH . 'wp-content/themes');
}
\register_theme_directory($custom_theme_path);


/**
 * ULTRA-EARLY FRONT-GATE INTERCEPTOR
 * Intercepts the raw execution array BEFORE WP-CLI validates versions against WordPress.org
 */
WP_CLI::add_hook('before_run_command', function ($command) {
  // Ensure we are targeted on a theme install command sequence
  if (empty($command) || count($command) < 3 || $command[0] !== 'theme' || $command[1] !== 'install') {
    return $command;
  }

  // Extract the target theme slug safely from the early command array
  $user_slug = $command[2];

  $config_file = IONOS_CUSTOM_DIR . '/stretch-extra-config.php';
  $config = file_exists($config_file) ? require $config_file : [];
  $themes_config = $config['themes'] ?? [];

  $matched_theme_url = null;
  foreach ($themes_config as $theme_entry) {
    if (isset($theme_entry['slug']) && $theme_entry['slug'] === $user_slug) {
      $matched_theme_url = $theme_entry['url'] ?? null;
      break;
    }
  }

  // If the slug isn't in our custom config, return the command and let standard WP-CLI handle it
  if (empty($matched_theme_url)) {
    return $command;
  }

  // MATCH FOUND: Take authoritative control immediately and bypass public lookups completely
  WP_CLI::log("Intercepted: Custom theme '{$user_slug}' matched config slug! Bypassing WordPress.org lookup...");
  WP_CLI::log("Downloading installation package from {$matched_theme_url}...");

  require_once ABSPATH . 'wp-admin/includes/file.php';

  $temp_zip = download_url($matched_theme_url);
  if (is_wp_error($temp_zip)) {
    WP_CLI::error("Failed to download custom theme package archive: " . $temp_zip->get_error_message());
  }

  $custom_theme_path = IONOS_CUSTOM_DIR . '/themes';
  if (! is_dir($custom_theme_path)) {
    wp_mkdir_p($custom_theme_path);
  }

  $unpacked = unzip_file($temp_zip, $custom_theme_path);
  @unlink($temp_zip);

  if (is_wp_error($unpacked)) {
    WP_CLI::error("Extraction failed: " . $unpacked->get_error_message());
  }

  // Remove theme from deleted tracking option list since it is active on disk again
  $deleted_themes = \get_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, []);
  $deleted_themes = array_filter($deleted_themes, fn ($theme) => $theme !== $user_slug);
  \update_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, array_values($deleted_themes), true);

  // Sync transient cache states immediately
  $theme_roots = \get_site_transient('theme_roots') ?: [];
  $theme_roots[$user_slug] = $custom_theme_path;
  \update_site_transient('theme_roots', $theme_roots);

  // Safely parse out runtime activation flags from the dynamic global runner
  $runner = WP_CLI::get_runner();
  if (isset($runner->assoc_args['activate'])) {
    \switch_theme($user_slug);
    WP_CLI::log("Activated theme '{$user_slug}'.");
  }

  wp_cache_delete('alloptions', 'options');
  delete_site_transient('update_themes');

  if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
  }

  WP_CLI::success("Theme installed successfully from configuration bundle mapping.");

  // Hard halt to terminate execution before WP-CLI's core engine triggers its remote download sequence
  WP_CLI::halt(0);
});


/**
 * WP-CLI HOOK ROUTINE (DELETE COMMAND)
 */
WP_CLI::add_hook('before_invoke:theme', function () {
  $runner     = \WP_CLI::get_runner();
  $subcommand = $runner->arguments[1] ?? '';
  $user_slug  = $runner->arguments[2] ?? '';

  if ($subcommand !== 'delete' || empty($user_slug)) {
    return;
  }

  $custom_theme_path = IONOS_CUSTOM_DIR . '/themes';
  $target_dir        = $custom_theme_path . '/' . $user_slug;

  if (! is_dir($target_dir)) {
    return;
  }

  // 1. ADD THE DELETED THEME INTO THE OPTION
  $deleted_themes   = \get_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, []);
  $deleted_themes[] = $user_slug;
  $deleted_themes   = array_unique($deleted_themes);
  \update_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, $deleted_themes, true);

  // 2. REALLY DELETE IT FROM THE FOLDER STRUCTURE
  $escaped_path = escapeshellarg($target_dir);
  exec("rm -rf {$escaped_path} 2>&1", $output, $return_code);

  if ($return_code === 0 && ! is_dir($target_dir)) {
    $theme_roots = \get_site_transient('theme_roots') ?: [];
    if (isset($theme_roots[$user_slug])) {
      unset($theme_roots[$user_slug]);
      \update_site_transient('theme_roots', $theme_roots);
    }

    wp_cache_delete('alloptions', 'options');
    delete_site_transient('update_themes');
    wp_cache_delete('theme_roots', 'themes');

    if (function_exists('apcu_clear_cache')) {
      apcu_clear_cache();
    }

    WP_CLI::success("Successfully updated tracking option and physically removed theme '{$user_slug}' from disk.");
  } else {
    WP_CLI::error("Option updated, but failed to delete physical folder. Output: " . implode("\n", $output));
  }

  WP_CLI::halt(0);
});

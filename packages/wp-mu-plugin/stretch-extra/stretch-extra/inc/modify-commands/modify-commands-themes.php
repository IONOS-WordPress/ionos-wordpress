<?php

namespace ionos\stretch_extra\secondary_theme_dir;

use WP_CLI;

if (! defined('WP_CLI') || ! WP_CLI) {
  return;
}

\add_action('init', function () {
  if (function_exists('register_theme_directory')) {
    $custom_themes_dir = dirname(__DIR__, 4) . '/themes';

    if (defined('ABSPATH')) {
      \register_theme_directory(ABSPATH . 'wp-content/themes');
    }
    \register_theme_directory($custom_themes_dir);
  }
});

WP_CLI::add_hook('before_invoke:theme', function () {
  $runner      = \WP_CLI::get_runner();
  $subcommand  = $runner->arguments[1] ?? '';
  $theme_slug  = $runner->arguments[2] ?? '';

  if ($subcommand !== 'delete' || empty($theme_slug)) {
    return;
  }

  $custom_themes_dir = dirname(__DIR__, 4) . '/themes';
  $target_dir        = $custom_themes_dir . '/' . $theme_slug;

  if (! is_dir($target_dir)) {
    return;
  }

  $deleted_themes_option = 'IONOS_CUSTOM_DELETED_THEMES_OPTION';

  $deleted_themes   = \get_option($deleted_themes_option, []);
  $deleted_themes[] = $theme_slug;
  $deleted_themes   = array_unique($deleted_themes);
  \update_option($deleted_themes_option, $deleted_themes, true);

  $themes_dir = realpath(WP_CONTENT_DIR . '/themes/');

  $target_dir_absolute = realpath($target_dir);

  if (
    $target_dir_absolute === false                  ||
    $themes_dir          === false                  ||
    strpos($target_dir_absolute, $themes_dir) !== 0 ||
    $target_dir_absolute === $themes_dir
  ) {
    throw new Exception('Security violation: Attempted to delete an invalid or unauthorized directory.');
  }

  $escaped_path = escapeshellarg($target_dir_absolute);
  exec("rm -rf {$escaped_path} 2>&1");

  \wp_cache_delete('alloptions', 'options');
  \wp_cache_delete('theme_roots', 'themes');

  if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
  }

  WP_CLI::success("Deleted '{$theme_slug}' theme.");
  WP_CLI::halt(0);
});

WP_CLI::add_hook('after_invoke:theme', function () {
  $runner      = \WP_CLI::get_runner();
  $subcommand  = $runner->arguments[1] ?? '';
  $theme_slug  = $runner->arguments[2] ?? '';

  if ($subcommand !== 'install' || empty($theme_slug)) {
    return;
  }

  $deleted_themes_option = 'IONOS_CUSTOM_DELETED_THEMES_OPTION';
  $deleted_themes        = \get_option($deleted_themes_option, []);

  if (! empty($deleted_themes) && in_array($theme_slug, $deleted_themes, true)) {
    $updated_themes = array_filter($deleted_themes, fn ($theme) => $theme !== $theme_slug);
    \update_option($deleted_themes_option, array_values($updated_themes), true);

    wp_cache_delete('alloptions', 'options');
    if (function_exists('apcu_clear_cache')) {
      apcu_clear_cache();
    }
  }
});

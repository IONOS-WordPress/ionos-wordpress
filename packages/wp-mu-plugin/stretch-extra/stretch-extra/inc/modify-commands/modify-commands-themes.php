<?php

namespace ionos\stretch_extra\secondary_theme_dir;

use WP_CLI;

if (! defined('WP_CLI') || ! WP_CLI) {
  return;
}

if (! defined('IONOS_CUSTOM_DIR')) {
  return;
}

$custom_theme_path = IONOS_CUSTOM_DIR . '/themes';

/**
 * Redirect theme root. Ignore /opt/WordPress/
 * Look only at mounted directory.
 */
\add_filter('theme_root', function ($theme_root) use ($custom_theme_path) {
  return $custom_theme_path;
}, 999);

/**
 * Override theme roots. Ignore /opt/WordPress/.
 * Only the mounted path is registered.
 */
\add_filter('theme_roots', function ($roots) use ($custom_theme_path) {
  return [
    'themes' => $custom_theme_path,
  ];
}, 999);

/**
 * Scan the mounted folder for style.css headers.
 */
\register_theme_directory($custom_theme_path);


\add_filter('pre_option_stylesheet_root', function () use ($custom_theme_path) {
  return $custom_theme_path;
});

\add_filter('pre_option_template_root', function () use ($custom_theme_path) {
  return $custom_theme_path;
});

/**
 * Force 'wp theme list' to completely jump over fake-deleted items.
 */
WP_CLI::add_hook('before_invoke:theme list', function () {
  $deleted_themes = \get_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, []);

  if (empty($deleted_themes)) {
    return;
  }

  // Fetch the full theme database array using the native WP API
  $all_themes = \wp_get_themes();
  $list_rows  = [];

  foreach ($all_themes as $slug => $theme_obj) {
    if (in_array($slug, $deleted_themes, true)) {
      continue;
    }

    $status = 'inactive';
    if (\get_stylesheet() === $slug) {
      $status = 'active';
    } elseif (\get_template() === $slug) {
      $status = 'parent';
    }

    $update_transient = \get_site_transient('update_themes');
    $has_update       = isset($update_transient->response[$slug]) ? 'available' : 'none';

    $list_rows[] = [
      'name'    => $slug,
      'status'  => $status,
      'update'  => $has_update,
      'version' => $theme_obj->get('Version'),
    ];
  }

  $runner     = WP_CLI::get_runner();
  $format     = $runner->assoc_args['format'] ?? 'table';
  $fields     = ['name', 'status', 'update', 'version'];

  if (isset($runner->assoc_args['fields'])) {
    $fields = explode(',', $runner->assoc_args['fields']);
  }

  WP_CLI\Utils\format_items($format, $list_rows, $fields);

  WP_CLI::halt(0);
});


WP_CLI::add_hook('before_invoke:theme', function () {
  $runner     = WP_CLI::get_runner();
  $subcommand = $runner->arguments[1] ?? '';
  $user_slug  = $runner->arguments[2] ?? '';
  $assoc_args = $runner->assoc_args;

  $intercept = ['activate', 'delete', 'install', 'update'];
  if (! in_array($subcommand, $intercept, true)) {
    return;
  }

  $custom_themes = get_custom_themes();

  if (! array_key_exists($user_slug, $custom_themes)) {
    return;
  }

  $deleted_themes = \get_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, []);

  switch ($subcommand) {
    case 'delete':
      $deleted_themes[] = $user_slug;
      $deleted_themes   = array_unique($deleted_themes);
      \update_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, $deleted_themes, true);
      break;

    case 'install':
    case 'update':
      // Reverse fake deletion when re-installing or updating
      $deleted_themes = array_filter($deleted_themes, fn ($theme) => $theme !== $user_slug);
      \update_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, array_values($deleted_themes), true);

      if (isset($assoc_args['activate'])) {
        \switch_theme($user_slug);
      }
      break;

    case 'activate':
      $deleted_themes = array_filter($deleted_themes, fn ($theme) => $theme !== $user_slug);
      \update_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, array_values($deleted_themes), true);
      \switch_theme($user_slug);
      break;
  }

  // Clear system and object caches to maintain state consistency across CLI execution cycles
  wp_cache_delete('alloptions', 'options');
  delete_site_transient('update_themes');

  if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
  }

  WP_CLI::success("Successfully performed {$subcommand} on theme '{$user_slug}' (State synchronized).");
  WP_CLI::halt(0);
});

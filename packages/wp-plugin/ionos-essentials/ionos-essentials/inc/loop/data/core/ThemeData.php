<?php

namespace ionos\essentials\loop\data\core;

use ionos\essentials\loop\data\DataProvider;
use function ionos\essentials\loop\normalize_version_string;

/**
 * Data provider for themes.
 */
class ThemeData extends DataProvider
{
  /**
   * Collects all theme related information.
   *
   * @return array
   */
  protected function collect_data()
  {
    /** WordPress Theme Administration API */
    require_once ABSPATH . 'wp-admin/includes/update.php';
    require_once ABSPATH . 'wp-admin/includes/theme.php';

    $themes_object = wp_get_themes();
    $theme_js      = wp_prepare_themes_for_js();

    wp_update_themes();
    $updates_transient = get_site_transient('update_themes');

    $parsed_themes = [];

    foreach ($theme_js as $theme) {
      if ($theme['active'] === true && ! empty($theme['parent'])) {
        $parent_theme = $themes_object[$theme['id']]->get_template();
      }

      $parsed_themes[] = [
        'id'                => $theme['id'],
        'version'           => normalize_version_string($theme['version'], true),
        'active'            => $theme['active'],
        'parent_theme_slug' => empty($theme['parent']) ? null : $themes_object[$theme['id']]->get_template(),
        'auto_update'       => $theme['autoupdate']['enabled'],
        'requires_php'      => normalize_version_string($themes_object[$theme['id']]->get('RequiresPHP')),
        'requires_wp'       => normalize_version_string($themes_object[$theme['id']]->get('RequiresWP')),
      ];
    }

    foreach ($parsed_themes as $key => $theme) {
      if ($theme['active'] === true || (isset($parent_theme) && $theme['id'] === $parent_theme)) {
        continue;
      }

      unset($parsed_themes[$key]);
    }

    return array_values($parsed_themes);
  }
}

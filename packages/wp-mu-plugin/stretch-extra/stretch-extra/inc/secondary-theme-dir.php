<?php

namespace ionos\stretch_extra\secondary_theme_dir;

use const ionos\stretch_extra\IONOS_CUSTOM_DIR;

defined('ABSPATH') || exit();

const IONOS_CUSTOM_THEMES_DIR            = IONOS_CUSTOM_DIR . '/themes';
const IONOS_CUSTOM_DELETED_THEMES_OPTION = 'IONOS_CUSTOM_DELETED_THEMES_OPTION';

\register_theme_directory(IONOS_CUSTOM_THEMES_DIR);

/**
 * @TODO: theme_file_uri filter will only fire if:
 *
 * The theme explicitly calls get_theme_file_uri('some/path.ext') in PHP code
 * Or calls get_parent_theme_file_uri('some/path.ext')
 *
 * Most themes will enqueue assets using wp_enqueue_style or wp_enqueue_script
 * with URLs generated via functions like get_stylesheet_directory_uri() or get_template_directory_uri()
 */
\add_filter('theme_file_uri', function ($url, $file) {
  // if its not one of our themes just return the original url
  // array_key_exists('SFS', $_SERVER) or constant IONOS_IS_STRETCH_SFS  is required to work in local wp-env
  if (! str_contains($url, '/extra/themes/') && ! defined('IONOS_IS_STRETCH_SFS')) {
    return $url;
  }

  // if we run in stretch sfs : replace the standard themes URL part with sfs stretch mapping
  return str_replace('/extra/themes/', '/wp-sfsxtra/themes/', $url);
}, 10, 2);

\add_filter('stylesheet_directory_uri', function ($stylesheet_dir_uri, $stylesheet, $theme_root_uri) {
  // if its not one of our themes just return the original url
  // array_key_exists('SFS', $_SERVER) or constant IONOS_IS_STRETCH_SFS  is required to work in local wp-env
  if (! str_ends_with($theme_root_uri, '/extra/themes') && ! defined('IONOS_IS_STRETCH_SFS')) {
    return $stylesheet_dir_uri;
  }

  // if we run in stretch sfs : replace the standard themes URL part with sfs stretch mapping
  return str_replace('/extra/themes/', '/wp-sfsxtra/themes/', $stylesheet_dir_uri);
}, 10, 3);

\add_filter('template_directory_uri', function ($template_dir_uri, $template, $theme_root_uri) {
  // if its not one of our themes just return the original url
  // array_key_exists('SFS', $_SERVER) or constant IONOS_IS_STRETCH_SFS  is required to work in local wp-env
  if (! str_ends_with($theme_root_uri, '/extra/themes') && ! defined('IONOS_IS_STRETCH_SFS')) {
    return $template_dir_uri;
  }

  // if we run in stretch sfs : replace the standard themes URL part with sfs stretch mapping
  return str_replace('/extra/themes', '/wp-sfsxtra/themes', $template_dir_uri);
}, 10, 3);

\add_filter('theme_root_uri', function ($theme_root_uri, $siteurl) {
  // array_key_exists('SFS', $_SERVER) or constant IONOS_IS_STRETCH_SFS  is required to work in local wp-env
  if (! str_ends_with($theme_root_uri, '/extra/themes') && ! defined('IONOS_IS_STRETCH_SFS')) {
    return $theme_root_uri;
  }

  // if we run in stretch sfs : replace the standard themes URL part with sfs stretch mapping
  return str_replace('/extra/themes', '/wp-sfsxtra/themes', $theme_root_uri);
}, 10, 2);

\add_filter('wp_prepare_themes_for_js', function ($prepared_themes) {
  $deleted_themes = \get_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, []);
  foreach ($deleted_themes as $deleted_theme) {
    unset($prepared_themes[$deleted_theme]);
  }

  return $prepared_themes;
}, 10, 1);

\add_filter('pre_set_site_transient_update_themes', __NAMESPACE__ . '\no_updates_for_custom_themes');
\add_filter('site_transient_update_themes', __NAMESPACE__ . '\no_updates_for_custom_themes');

/*
  @TODO: the theme can be preset in the database template
  Alternative workaround : Alex can set the theme to extendable when provisioning the account
  if this is the case the code below can be removed
*/
\add_action('muplugins_loaded', function () {
  $is_initialized = \get_option('stretch_extra_extendable_theme_dir_initialized', false) || \get_option(
    'stylesheet'
  ) === 'extendable';
  if ($is_initialized !== false) {
    return;
  }

  // fixes wp-env local development where the theme may not available in the themes directory
  // depending on latest pnpm stretch-extra --install||clean call
  if (is_dir(IONOS_CUSTOM_THEMES_DIR . '/extendable')) {
    \switch_theme('extendable');
  }

  \update_option('stretch_extra_extendable_theme_dir_initialized', true, true);
}, 1);

\add_action('delete_theme', function ($slug) {
  // Only process themes from our custom directory
  $theme = \wp_get_theme($slug);
  if (! $theme->exists() || ! str_contains($theme->get_stylesheet_directory(), IONOS_CUSTOM_THEMES_DIR)) {
    return;
  }
  $deleted_themes = \get_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, []);

  $deleted_themes[] = $slug;
  $deleted_themes   = array_unique($deleted_themes);

  \update_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, $deleted_themes, true);
}, 10, 1);

\add_action(
  'switch_theme',
  function ($new_name, $new_theme, $old_theme) {
    if (! str_contains($new_theme->get_stylesheet_directory(), IONOS_CUSTOM_THEMES_DIR)) {
      return;
    }

    $theme_key = $new_theme->get_stylesheet();

    // Remove from deleted themes list if it was marked as deleted
    $deleted_themes = \get_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, []);
    $theme_key      = $new_theme->get_stylesheet();
    $deleted_themes = array_filter($deleted_themes, fn ($theme) => $theme !== $theme_key);
    \update_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, $deleted_themes, true);
  },
  10,
  3
);

\add_action('admin_print_scripts-theme-install.php', function () {
  $deleted_themes = \get_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, []);

  $custom_themes = get_custom_themes();

  // JavaScript to modify _wpThemeSettings
  printf(<<<'HTML'
<script type="text/javascript">
  const deletedThemes = JSON.parse('%s');
  document.addEventListener('DOMContentLoaded', function() {
    if (typeof _wpThemeSettings !== 'undefined') {
      if (_wpThemeSettings && _wpThemeSettings.installedThemes) {
        _wpThemeSettings.installedThemes = _wpThemeSettings.installedThemes.filter(theme => !deletedThemes.includes(theme));
      }
    }
  });
</script>
HTML
    , \wp_json_encode(array_values($deleted_themes)));
});

\add_action('wp_ajax_install-theme', function () {
  if (! isset($_POST['_ajax_nonce']) || ! check_ajax_referer('updates', '_ajax_nonce', false)) {
    \wp_die('Security check failed');
  }

  $slug = $_POST['slug'] ?? '';

  if (empty($slug)) {
    return;
  }
  $slug = \sanitize_text_field($slug);

  $custom_themes = get_custom_themes();
  if (! array_key_exists($slug, $custom_themes)) {
    return;
  }

  $deleted_themes = \get_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, []);
  $deleted_themes = array_filter($deleted_themes, fn ($theme) => $theme !== $slug);
  \update_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, $deleted_themes, true);

  $response = [
    'install'     => 'theme',
    'slug'        => $slug,
    'themeName'   => $custom_themes[$slug]->get('Name'),
    'activateUrl' => \admin_url("themes.php?action=activate&stylesheet={$slug}") . '&_wpnonce=' . \wp_create_nonce(
      'switch-theme_' . $slug
    ),
  ];
  wp_send_json_success($response);
}, 0);

function no_updates_for_custom_themes($value)
{
  if (! isset($value->checked)) {
    return $value;
  }

  $custom_themes = get_custom_themes();

  // Remove custom themes from update check
  foreach (array_keys($custom_themes) as $theme_slug) {
    unset($value->checked[$theme_slug]);
    if (isset($value->response[$theme_slug])) {
      unset($value->response[$theme_slug]);
    }
    if (isset($value->no_update[$theme_slug])) {
      unset($value->no_update[$theme_slug]);
    }
  }

  return $value;
}

function get_custom_themes()
{
  $themes = \wp_get_themes();
  return array_filter($themes, function ($theme) {
    return str_contains($theme->get_stylesheet_directory(), IONOS_CUSTOM_THEMES_DIR);
  });
}

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
\add_filter('theme_file_uri', __NAMESPACE__ . '\theme_file_uri', 10, 2);
\add_filter('stylesheet_directory_uri', __NAMESPACE__ . '\stylesheet_directory_uri', 10, 3);
\add_filter('template_directory_uri', __NAMESPACE__ . '\template_directory_uri', 10, 3);
\add_filter('theme_root_uri', __NAMESPACE__ . '\theme_root_uri', 10, 2);
\add_filter('wp_prepare_themes_for_js', __NAMESPACE__ . '\filter_prepared_themes_for_js', 10, 1);
\add_filter('pre_set_site_transient_update_themes', __NAMESPACE__ . '\no_updates_for_custom_themes');
\add_filter('site_transient_update_themes', __NAMESPACE__ . '\no_updates_for_custom_themes');

/*
  @TODO: the theme can be preset in the database template
  Alternative workaround : Alex can set the theme to extendable when provisioning the account
  if this is the case the code below can be removed
*/
\add_action('muplugins_loaded', __NAMESPACE__ . '\muplugins_loaded', 1);
\add_action('delete_theme', __NAMESPACE__ . '\delete_theme', 10, 1);
\add_action('switch_theme', __NAMESPACE__ . '\switch_theme', 10, 3);
\add_action('admin_print_scripts-theme-install.php', __NAMESPACE__ . '\admin_print_scripts_theme_install_php');
\add_action('rest_api_init', __NAMESPACE__ . '\rest_api_init');

/////////////////////////////////
// Filter Functions
/////////////////////////////////
function stylesheet_directory_uri($stylesheet_dir_uri, $stylesheet, $theme_root_uri)
{
  // if its not one of our themes just return the original url
  // array_key_exists('SFS', $_SERVER) is required to work in local wp-env
  if (! str_ends_with($theme_root_uri, '/extra/themes') && ! array_key_exists('SFS', $_SERVER)) {
    return $stylesheet_dir_uri;
  }

  // if we run in stretch sfs : replace the standard themes URL part with sfs stretch mapping
  return str_replace('/extra/themes/', '/wp-sfsxtra/themes/', $stylesheet_dir_uri);
}

function theme_file_uri($url, $file)
{
  // if its not one of our themes just return the original url
  // array_key_exists('SFS', $_SERVER) is required to work in local wp-env
  if (! str_contains($url, '/extra/themes/') && ! array_key_exists('SFS', $_SERVER)) {
    return $url;
  }

  // if we run in stretch sfs : replace the standard themes URL part with sfs stretch mapping
  return str_replace('/extra/themes/', '/wp-sfsxtra/themes/', $url);
}

function template_directory_uri($template_dir_uri, $template, $theme_root_uri)
{
  // if its not one of our themes just return the original url
  // array_key_exists('SFS', $_SERVER) is required to work in local wp-env
  if (! str_ends_with($theme_root_uri, '/extra/themes') && ! array_key_exists('SFS', $_SERVER)) {
    return $template_dir_uri;
  }

  // if we run in stretch sfs : replace the standard themes URL part with sfs stretch mapping
  return str_replace('/extra/themes', '/wp-sfsxtra/themes', $template_dir_uri);
}

function theme_root_uri($theme_root_uri, $siteurl)
{
  // array_key_exists('SFS', $_SERVER) is required to work in local wp-env
  if (! str_ends_with($theme_root_uri, '/extra/themes') && ! array_key_exists('SFS', $_SERVER)) {
    return $theme_root_uri;
  }

  // if we run in stretch sfs : replace the standard themes URL part with sfs stretch mapping
  return str_replace('/extra/themes', '/wp-sfsxtra/themes', $theme_root_uri);
}

function filter_prepared_themes_for_js($prepared_themes)
{
  $deleted_themes = \get_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, []);
  foreach ($deleted_themes as $deleted_theme) {
    unset($prepared_themes[$deleted_theme]);
  }

  return $prepared_themes;
}

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

//////////////////////////////////
// Action Functions
//////////////////////////////////
function muplugins_loaded()
{
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
}

function delete_theme($stylesheet)
{
  // Only process themes from our custom directory
  $theme = \wp_get_theme($stylesheet);
  error_log('Try to delete theme: ' . $stylesheet);
  if (! $theme->exists() || ! str_contains($theme->get_stylesheet_directory(), IONOS_CUSTOM_THEMES_DIR)) {
    return;
  }
  mark_custom_theme_as_deleted($stylesheet);
}

function switch_theme($new_name, $new_theme, $old_theme)
{
  // Only process themes from our custom directory
  if (! str_contains($new_theme->get_stylesheet_directory(), IONOS_CUSTOM_THEMES_DIR)) {
    return;
  }

  $theme_key = $new_theme->get_stylesheet();

  // Remove from deleted themes list if it was marked as deleted
  $deleted_themes = \get_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, []);
  $theme_key      = $new_theme->get_stylesheet();
  $deleted_themes = array_filter($deleted_themes, fn ($theme) => $theme !== $theme_key);
  \update_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, $deleted_themes, true);
}

function admin_print_scripts_theme_install_php()
{
  $deleted_themes = \get_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, []);

  $custom_themes = get_custom_themes();

  // JavaScript to modify _wpThemeSettings
  printf(<<<'HTML'
<script type="text/javascript">
  const deletedThemes = JSON.parse('%s');
  const ionosExtraCustomThemes = %s;
  document.addEventListener('DOMContentLoaded', function() {
    if (typeof _wpThemeSettings !== 'undefined') {
      if (_wpThemeSettings && _wpThemeSettings.installedThemes) {
        _wpThemeSettings.installedThemes = _wpThemeSettings.installedThemes.filter(theme => !deletedThemes.includes(theme));
      }
    }
  });
</script>
HTML
    , \wp_json_encode(array_values($deleted_themes)), \wp_json_encode(array_keys($custom_themes)));

  printf(
    <<<'HTML'
<script type="text/javascript">
  document.addEventListener('DOMContentLoaded', function() {
    const targetNode = document.querySelector('.theme-browser');
    const callback = (mutationsList, observer) => {
      for (const mutation of mutationsList) {
        if (mutation.type === 'childList') {
          for (const themeSlug of ionosExtraCustomThemes) {
            const newBtn = document.querySelector(`[data-slug=${themeSlug}] a.theme-install`);
            if (!newBtn || newBtn.dataset.listenerAttached === 'true') {
                continue;
            }
            newBtn.addEventListener('click',() => ionosStretchNewButtonClick(event, themeSlug));
            newBtn.dataset.listenerAttached = 'true';
            newBtn.removeAttribute('data-slug');
            newBtn.removeEventListener('click', arguments.callee);
          }

          installButton = document.querySelector(`.theme-install-overlay a.theme-install`);
          if (installButton) {
            installButton.addEventListener('click',() => ionosStretchNewButtonClick(event, installButton.dataset.slug) );
            installButton.classList.remove('theme-install');
          }
        }
      }
      return;
    };

    function ionosStretchNewButtonClick(event, themeSlug) {
      event.stopPropagation();
      event.preventDefault();

      fetch('/wp-json/ionos/stretch-extra/v1/restore-theme', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': '%s'
        },
        body: JSON.stringify({ theme_slug: themeSlug })
      }).then(response => response.json()).then(data => {

        const noticeDiv = document.createElement('div');
        noticeDiv.className = 'notice notice-success notice-alt';
        noticeDiv.innerHTML = `<p>${data.message}</p>`;
        event.target.closest('.theme').appendChild(noticeDiv);

        wp.updates.installThemeSuccess({slug: themeSlug, activateUrl: data.activate_url});
        _wpThemeSettings.installedThemes.push(themeSlug)
      });

      return false;
    }
    const observer = new MutationObserver(callback);
    observer.observe(targetNode, { childList: true, subtree: true });
  });
</script>
HTML
    ,
    \wp_create_nonce('wp_rest')
  );
}

function rest_api_init()
{
  \register_rest_route('ionos/stretch-extra/v1', '/restore-theme', [
    'methods'             => 'POST',
    'callback'            => __NAMESPACE__ . '\handle_restore_theme',
    'permission_callback' => function () {
      return \current_user_can('install_themes');
    },
    'args'                => [
      'theme_slug' => [
        'required'          => true,
        'type'              => 'string',
        'sanitize_callback' => '\sanitize_text_field',
        'validate_callback' => function ($param) {
          return ! empty($param) && is_string($param);
        },
      ],
    ],
  ]);
}

///////////////////////////////////
// REST API Handlers
///////////////////////////////////
function handle_restore_theme(\WP_REST_Request $request): \WP_REST_Response
{
  $theme_slug = $request->get_param('theme_slug');

  // Remove theme from deleted themes list
  $deleted_themes = \get_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, []);
  $deleted_themes = array_filter($deleted_themes, fn ($theme) => $theme !== $theme_slug);
  \update_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, $deleted_themes, true);

  // Check if theme exists in custom directory
  $theme = \wp_get_theme($theme_slug);
  if (! $theme->exists() || ! str_contains($theme->get_stylesheet_directory(), IONOS_CUSTOM_THEMES_DIR)) {
    return new \WP_REST_Response([
      'success' => false,
      'message' => \esc_html__('Theme not found in custom directory', 'stretch-extra'),
    ], 404);
  }

  return new \WP_REST_Response([
    'success'      => true,
    'message'      => \esc_html__('Installed', 'default'),
    'theme_slug'   => $theme_slug,
    'activate_url' =>
      \admin_url("themes.php?action=activate&stylesheet={$theme_slug}") . '&_wpnonce=' . \wp_create_nonce(
        'switch-theme_' . $theme_slug
      ),
  ], 200);
}

///////////////////////////////////
// Helper Functions
///////////////////////////////////
function get_custom_themes()
{
  $themes = \wp_get_themes();
  return array_filter($themes, function ($theme) {
    return str_contains($theme->get_stylesheet_directory(), IONOS_CUSTOM_THEMES_DIR);
  });
}

function mark_custom_theme_as_deleted($theme_key)
{
  $deleted_themes = \get_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, []);

  $deleted_themes[] = $theme_key;
  $deleted_themes   = array_unique($deleted_themes);

  \update_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, $deleted_themes, true);
}

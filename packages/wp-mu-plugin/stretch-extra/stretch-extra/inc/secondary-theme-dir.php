<?php

namespace ionos\stretch_extra\secondary_theme_dir;

use const ionos\stretch_extra\IONOS_CUSTOM_DIR;

defined('ABSPATH') || exit();

const IONOS_CUSTOM_THEMES_DIR            = IONOS_CUSTOM_DIR . '/themes';
const IONOS_CUSTOM_DELETED_THEMES_OPTION = 'IONOS_CUSTOM_DELETED_THEMES_OPTION';

\register_theme_directory(IONOS_CUSTOM_THEMES_DIR);

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
});

/**
 * Register custom theme directory URL handling
 * This allows get_stylesheet_directory_uri() to return correct URLs for our custom themes
 */
\add_filter(
  'stylesheet_directory_uri',
  function ($stylesheet_dir_uri, $stylesheet, $theme_root_uri) {
    // if its not one of our themes just return the original url
    // array_key_exists('SFS', $_SERVER) is required to work in local wp-env
    if (! str_ends_with($theme_root_uri, '/extra/themes') && ! array_key_exists('SFS', $_SERVER)) {
      return $stylesheet_dir_uri;
    }

    // if we run in stretch sfs : replace the standard themes URL part with sfs stretch mapping
    return str_replace('/extra/themes/', '/wp-sfsxtra/themes/', $stylesheet_dir_uri);
  },
  10,
  3
);

/**
 * @TODO: theme_file_uri filter will only fire if:
 *
 * The theme explicitly calls get_theme_file_uri('some/path.ext') in PHP code
 * Or calls get_parent_theme_file_uri('some/path.ext')
 *
 * Most themes will enqueue assets using wp_enqueue_style or wp_enqueue_script
 * with URLs generated via functions like get_stylesheet_directory_uri() or get_template_directory_uri()
 */
\add_filter(
  'theme_file_uri',
  function ($url, $file) {
    // if its not one of our themes just return the original url
    // array_key_exists('SFS', $_SERVER) is required to work in local wp-env
    if (! str_contains($url, '/extra/themes/') && ! array_key_exists('SFS', $_SERVER)) {
      return $url;
    }

    // if we run in stretch sfs : replace the standard themes URL part with sfs stretch mapping
    return str_replace('/extra/themes/', '/wp-sfsxtra/themes/', $url);
  },
  10,
  2
);

/**
 * Filters the active theme directory URI.
 *
 * @param string $template_dir_uri The URI to the active theme's directory.
 * @param string $template         The name of the active theme.
 * @param string $theme_root_uri     The URI of the theme root (usually /wp-content/themes).
 */
\add_filter(
  'template_directory_uri',
  function ($template_dir_uri, $template, $theme_root_uri) {
    // if its not one of our themes just return the original url
    // array_key_exists('SFS', $_SERVER) is required to work in local wp-env
    if (! str_ends_with($theme_root_uri, '/extra/themes') && ! array_key_exists('SFS', $_SERVER)) {
      return $template_dir_uri;
    }

    // if we run in stretch sfs : replace the standard themes URL part with sfs stretch mapping
    return str_replace('/extra/themes', '/wp-sfsxtra/themes', $template_dir_uri);
  },
  10,
  3
);

\add_filter(
  'theme_root_uri',
  function ($theme_root_uri, $siteurl) {
    // array_key_exists('SFS', $_SERVER) is required to work in local wp-env
    if (! str_ends_with($theme_root_uri, '/extra/themes') && ! array_key_exists('SFS', $_SERVER)) {
      return $theme_root_uri;
    }

    // if we run in stretch sfs : replace the standard themes URL part with sfs stretch mapping
    return str_replace('/extra/themes', '/wp-sfsxtra/themes', $theme_root_uri);
  },
  10,
  2
);

/**
 * Handle theme deletion in custom theme directory
 */
\add_action('delete_theme', function ($stylesheet) {
  // Only process themes from our custom directory
  $theme = \wp_get_theme($stylesheet);
  error_log('Try to delete theme: ' . $stylesheet);
  if (! $theme->exists() || ! str_contains($theme->get_stylesheet_directory(), IONOS_CUSTOM_THEMES_DIR)) {
    return;
  }
  error_log('Theme deletion detected for custom theme: ' . $stylesheet);
  mark_custom_theme_as_deleted($stylesheet);
}, 10, 1);

function mark_custom_theme_as_deleted($theme_key)
{
  $deleted_themes = \get_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, []);

  $deleted_themes[] = $theme_key;
  $deleted_themes   = array_unique($deleted_themes);

  \update_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, $deleted_themes, true);
}

/**
 * Filter installed themes to exclude deleted custom themes
 */
\add_filter('wp_prepare_themes_for_js', function ($prepared_themes) {
  $deleted_themes = \get_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, []);
  if (empty($deleted_themes)) {
    return $prepared_themes;
  }

  foreach ($deleted_themes as $deleted_theme) {
    unset($prepared_themes[$deleted_theme]);
  }

  return $prepared_themes;
});

/**
 * Handle theme activation in custom theme directory
 */
\add_action('switch_theme', function ($new_name, $new_theme, $old_theme) {
  // Only process themes from our custom directory
  if (! str_contains($new_theme->get_stylesheet_directory(), IONOS_CUSTOM_THEMES_DIR)) {
    return;
  }

  error_log('Theme activation detected for custom theme: ' . $new_name);

  $theme_key = $new_theme->get_stylesheet();

  // Remove from deleted themes list if it was marked as deleted
  $deleted_themes = \get_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, []);
  $theme_key      = $new_theme->get_stylesheet();
  $deleted_themes = array_filter($deleted_themes, fn ($theme) => $theme !== $theme_key);
  \update_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, $deleted_themes, true);
}, 10, 3);

/**
 * Modify _wpThemeSettings JavaScript variable on theme-install page
 * to indicate deleted custom themes
 */
\add_action('admin_print_scripts-theme-install.php', function () {
  $deleted_themes = \get_option(IONOS_CUSTOM_DELETED_THEMES_OPTION, []);

  if (empty($deleted_themes)) {
    return;
  }

  // JavaScript to modify _wpThemeSettings
  printf(<<<'HTML'
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
  if (typeof _wpThemeSettings !== 'undefined') {
    // List of deleted custom themes to exclude
    const deletedThemes = %s;
    // Override _wpThemeSettings if needed
    if (_wpThemeSettings && _wpThemeSettings.installedThemes) {
      _wpThemeSettings.installedThemes = _wpThemeSettings.installedThemes.filter(theme => !deletedThemes.includes(theme));
    }
  }
});
</script>
HTML
    , \wp_json_encode($deleted_themes));
});

/**
 * Add JavaScript to theme-install page to handle custom theme directory behavior
 */
\add_action('admin_print_scripts-theme-install.php', function () {

  echo <<<'HTML'
<script type="text/javascript">
  document.addEventListener('DOMContentLoaded', function() {
    const targetNode = document.querySelector('.theme-browser');
    const callback = (mutationsList, observer) => {
        for (const mutation of mutationsList) {
            if (mutation.type === 'childList') {

              const newBtn = document.querySelector('[data-slug=go] a.theme-install');
              if (!newBtn || newBtn.dataset.listenerAttached === 'true') {
                  return;
              }

              newBtn.addEventListener('click', function(event) {
                event.stopPropagation();
                event.preventDefault();

                const noticeDiv = document.createElement('div');
                noticeDiv.className = 'notice notice-success notice-alt';
                noticeDiv.innerHTML = '<p>Installiert</p>';
                event.target.closest('.theme').appendChild(noticeDiv);

                wp.updates.installThemeSuccess({slug:'go', activateUrl: 'http://localhost:8888/wp-admin/themes.php?action=activate&stylesheet=go&nonce=example-nonce'});
                _wpThemeSettings.installedThemes.push('go')

                return false;
              });
              newBtn.dataset.listenerAttached = 'true';

            }
        }
    };
    const observer = new MutationObserver(callback);
    observer.observe(targetNode, { childList: true, subtree: true });
  });
</script>
HTML;
});

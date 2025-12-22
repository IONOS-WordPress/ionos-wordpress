<?php

namespace ionos\stretch_extra\secondary_plugin_dir;

use const ionos\stretch_extra\IONOS_CUSTOM_DIR;

defined('ABSPATH') || exit();

const IONOS_CUSTOM_THEMES_DIR  = IONOS_CUSTOM_DIR . '/themes';

\register_theme_directory( IONOS_CUSTOM_THEMES_DIR );

/*
  @TODO: the theme can be preset in the database template
  Alternative workaround : Alex can set the theme to extendable when provisioning the account
  if this is the case the code below can be removed
*/
\add_action('muplugins_loaded', function() {
  $is_initialized = get_option('stretch_extra_extendable_theme_dir_initialized', false);
  if($is_initialized !== false) {
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
    error_log("inside stylesheet_directory_uri filter");
    error_log(
      print_r( [
        'stylesheet_dir_uri' => $stylesheet_dir_uri,
        'stylesheet'         => $stylesheet,
        'theme_root_uri'     => $theme_root_uri,
      ], true)
    );
    /*
    Array
    (
        [stylesheet_dir_uri] => https://lars1.stretch.vision/extra/themes/extendable
        [stylesheet] => extendable
        [theme_root_uri] => https://lars1.stretch.vision/extra/themes
    )
    */

    // if its not one of our themes just return the original url
    // array_key_exists('SFS', $_SERVER) is required to work in local wp-env
    if (!str_ends_with($theme_root_uri, '/extra/themes') && !array_key_exists('SFS', $_SERVER)) {
      return $stylesheet_dir_uri;
    }

    error_log(sprintf(
      'detected stretch sfs theme stylesheet directory uri - adjusting url %s to %s',
      $stylesheet_dir_uri,
      str_replace("/extra/themes/", "/wp-sfsxtra/themes/", $stylesheet_dir_uri))
    );

    // if we run in stretch sfs : replace the standard themes URL part with sfs stretch mapping
    return str_replace("/extra/themes/", "/wp-sfsxtra/themes/", $stylesheet_dir_uri);
  },
  10, 3
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
  function( $url, $file ) {
    error_log("inside theme_file_uri filter");
    error_log(
      print_r( [
        'url'  => $url,
        'file' => $file
      ], true)
    );

    /*
      [url] => https://lars1.stretch.vision/extra/themes/extendable/assets/fonts/baloo-tamma-2/baloo-tamma-2_wght.woff2
      [file] => assets/fonts/baloo-tamma-2/baloo-tamma-2_wght.woff2
    */

    // if its not one of our plugins just return the original url
    // array_key_exists('SFS', $_SERVER) is required to work in local wp-env
    if (!str_contains($url, "/extra/themes/") && !array_key_exists('SFS', $_SERVER)) {
      return $url;
    }

    // if we run in stretch sfs : replace the standard themes URL part with sfs stretch mapping
    return str_replace("/extra/themes/", "/wp-sfsxtra/themes/", $url);
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
  function ( $template_dir_uri, $template, $theme_root_uri ) {
    error_log("inside template_directory_uri filter");
    error_log(
      print_r( [
        'template_dir_uri' => $template_dir_uri,
        'template'         => $template,
        'theme_root_uri'     => $theme_root_uri,
      ], true)
    );
    /*
    Array
    (
        [template_dir_uri] => https://lars1.stretch.vision/extra/themes/extendable
        [template] => extendable
        [theme_root_uri] => https://lars1.stretch.vision/extra/themes
    )
    */

    // if its not one of our themes just return the original url
    // array_key_exists('SFS', $_SERVER) is required to work in local wp-env
    if (!str_ends_with($theme_root_uri, '/extra/themes') && !array_key_exists('SFS', $_SERVER)) {
      return $template_dir_uri;
    }

    error_log(sprintf(
      'detected stretch sfs theme template uri - adjusting url %s to %s',
      $template_dir_uri,
      str_replace("/extra/themes", "/wp-sfsxtra/themes", $template_dir_uri))
    );

    // if we run in stretch sfs : replace the standard themes URL part with sfs stretch mapping
    return str_replace("/extra/themes", "/wp-sfsxtra/themes", $template_dir_uri);
  },
  10,
  3
);

\add_filter(
  'theme_root_uri',
  function($theme_root_uri, $siteurl) {
    error_log("inside theme_root_uri filter");
    error_log(
      print_r( [
        'theme_root_uri' => $theme_root_uri,
        'siteurl'         => $siteurl,
      ], true)
    );

    // array_key_exists('SFS', $_SERVER) is required to work in local wp-env
    if (!str_ends_with($theme_root_uri, '/extra/themes') && !array_key_exists('SFS', $_SERVER)) {
      return $theme_root_uri;
    }

    error_log(sprintf(
      'detected stretch sfs theme root uri - adjusting url %s to %s',
      $theme_root_uri,
      str_replace("/extra/themes", "/wp-sfsxtra/themes", $theme_root_uri))
    );

    // if we run in stretch sfs : replace the standard themes URL part with sfs stretch mapping
    return str_replace("/extra/themes", "/wp-sfsxtra/themes", $theme_root_uri);
  },
  10,
  2
);

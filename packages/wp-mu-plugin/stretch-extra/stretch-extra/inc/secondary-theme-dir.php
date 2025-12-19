<?php

namespace ionos\stretch_extra\secondary_plugin_dir;

use const ionos\stretch_extra\IONOS_CUSTOM_DIR;

defined('ABSPATH') || exit();

const IONOS_CUSTOM_THEMES_DIR  = IONOS_CUSTOM_DIR . '/themes';

// \register_theme_directory( IONOS_CUSTOM_THEMES_DIR );

ini_set('error_log', true);

// @TODO: hack just for beta
\add_action('plugins_loaded', function() {
  // \delete_option('stretch_extra_extendable_theme_dir_initialized');
  $is_initialized = get_option('stretch_extra_extendable_theme_dir_initialized', false);
  if($is_initialized !== false) {
    return;
  }

  \update_option('stretch_extra_extendable_theme_dir_initialized', true, true);

  require_once ABSPATH . 'wp-admin/includes/file.php';
  if(!WP_Filesystem()) {
    error_log('WP_Filesystem initialization failed in stretch-extra secondary theme dir');
    return;
  }

  global $wp_filesystem;

  $result = \copy_dir(
    IONOS_CUSTOM_THEMES_DIR . '/extendable',
    WP_CONTENT_DIR . '/themes/extendable'
  );

  if (is_wp_error($result)) {
    error_log('Failed to copy extendable theme to themes directory: ' . $result->get_error_message());
    return;
  }
});

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

  \switch_theme('extendable');
});

return;

/**
 * Register custom theme directory URL handling
 * This allows get_stylesheet_directory_uri() to return correct URLs for our custom themes
 */
\add_filter(
hook_name: 'stylesheet_directory_uri',
  callback: function ($stylesheet_dir_uri, $stylesheet, $theme_root_uri) {
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
  accepted_args: 3
);

\add_filter( 'theme_file_uri', function( $url, $file ) {
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
  accepted_args: 2
);

/**
 * Filters the active theme directory URI.
 *
 * @param string $template_dir_uri The URI to the active theme's directory.
 * @param string $template         The name of the active theme.
 * @param string $theme_root_uri     The URI of the theme root (usually /wp-content/themes).
 */
add_filter(
    hook_name: 'template_directory_uri',
    callback: function ( $template_dir_uri, $template, $theme_root_uri ) {

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
      str_replace("/extra/themes/", "/wp-sfsxtra/themes/", $template_dir_uri))
    );

    // if we run in stretch sfs : replace the standard themes URL part with sfs stretch mapping
    return str_replace("/extra/themes/", "/wp-sfsxtra/themes/", $template_dir_uri);
  },
  accepted_args: 3
);




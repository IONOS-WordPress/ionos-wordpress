<?php

namespace ionos\essentials\login;

// exit if accessed directly
if (! defined('ABSPATH')) {
  exit();
}


add_action( 'init', function() {
  if ( false === in_array( $GLOBALS['pagenow'], [ 'wp-login.php', 'wp-register.php' ], true ) ) {
    return;
  }

  add_action(
    'login_enqueue_scripts',
    function () {
      wp_enqueue_style( 'ionos-login-redesign', plugins_url( 'style.css', __FILE__ ), [], filemtime( __DIR__ . '/style.css' )
      );
    }
  );

  add_filter( 'login_body_class', function ( $classes ) {
    $classes[] = 'ionos-group-page';

    return $classes;
  }
  );

  add_action(
    'login_header',
    function () {
      global $interim_login;
      $template = __DIR__ . "/template.php";
      if ( $interim_login || ! file_exists( $template ) ) {
        return;
      }

      load_template( $template );
    }
  );
});

function get_brand_config(): array|null {
  static $config = null;

  if (null !== $config ) {
    return $config;
  }

  $brand_config = __DIR__ . '/configs/' . get_option('ionos_group_brands', 'ionos') . '.php';
  if (! file_exists($brand_config)) {
    return null;
  }

  require_once $brand_config;

  $config = get_config_data();
  return $config;
}



<?php

namespace ionos\essentials\security;

use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION;
use const ionos\essentials\security\IONOS_SECURITY_FEATURE_OPTION_PEL;

// exit if accessed directly
if (! defined('ABSPATH')) {
  exit();
}

add_action( 'login_init' , function () {
  remove_filter( 'authenticate', 'wp_authenticate_email_password', 20 );
  add_filter( 'authenticate', __NAMESPACE__ . '\email_auth_filter' , 200, 2 );
} );

function email_auth_filter( $user, $username ) {
  if ( false !== strpos( $username, '@' ) ) {
    return new \WP_Error(
      'email_login_inactive',
      __( '<strong>Error</strong>: The login with an email address is deactivated for this website. Please use your username instead.', 'ionos-security' )
    );
  }

  return $user;
}

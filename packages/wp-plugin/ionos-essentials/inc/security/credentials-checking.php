<?php

namespace ionos\essentials\security;

const LEAKED_CREDENTIALS_FLAG_NAME = 'ionos_compromised_credentials_check_leak_detected_v2';
use const ionos\essentials\PLUGIN_DIR;

// exit if accessed directly
if (! defined('ABSPATH')) {
  exit();
}

enable_credentials_checking();

function check_passwords( $user_login, $pass1, $pass2 ) {
  if ( $pass1 !== $pass2 ) {
    return;
  }

  if ( ! is_leaked( $user_login, $pass1 ) ) {
    $user = wp_get_current_user();
    update_user_meta( $user->ID, LEAKED_CREDENTIALS_FLAG_NAME, false );
    return;
  }

  add_action(
    'user_profile_update_errors',
    /**
     * Adds an error and shows an admin notice if the user tries to set a password which has been leaked.
     *
     * @param $errors WP_Error[]
     */
    function ( $errors ) {
      $errors->add( 'password_leaked', __( 'The entered password has already been leaked. Please choose another one.', 'ionos-security' ) );
    },
    10,
    1
  );
}

function is_leaked( $user_login, $password ) {
  $hash   = strtoupper( sha1( $password ) );
  $prefix = substr( $hash, 0, 5 );
  $suffix = substr( $hash, 5 );

  $url      = "https://api.pwnedpasswords.com/range/{$prefix}";
  $response = wp_remote_get( $url );
  if ( is_wp_error( $response ) ) {
    return null;
  }

  return strpos( wp_remote_retrieve_body( $response ), $suffix ) !== false;
}

// if ( ! is_ssl() ) {
//   return;
// }

function enable_credentials_checking() {
  register_wp_login_hooks();

  add_action( 'admin_notices', function() {
    if ( has_leaked_flag( get_current_user_id() ) ) {
      $class   = 'notice notice-error';
      $message = __( 'We detected that your password has been leaked and suggest that you change it as soon as possible.', 'ionos-security' );
      $link    = sprintf(
        '<a href="%s">%s</a>',
        esc_url( get_edit_profile_url() ),
        esc_html__( 'Click here to edit your profile settings.', 'ionos-security' )
      );
      printf( '<div class="%s"><p>%s %s</p></div>', esc_attr( $class ), esc_html( $message ), esc_url( $link ) );
    }
  } );
}

function register_wp_login_hooks() {
  add_action( 'check_passwords', __NAMESPACE__ . '\check_passwords' , 10, 3 );

  if ( is_login() ) {
    add_action( 'login_form_icc_leak_detected', __NAMESPACE__ . '\show_view'  );

    add_action( 'validate_password_reset', __NAMESPACE__ . '\validate_password_reset' , 10, 2 );
    add_filter( 'authenticate', __NAMESPACE__ . '\authenticate' , 100, 3 );
  }
}

function has_leaked_flag( $user_id ) {
  return (bool) get_user_meta( $user_id, LEAKED_CREDENTIALS_FLAG_NAME, true );
}

function show_view() {
  $action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

  if ( $action === 'icc_leak_detected' ) {
    $template_path = IONOS_SECURITY_DIR . '/inc/Views/CredentialsChecking/leak-detected.php';
  }

  if ( ! is_readable( $template_path ) ) {
    return;
  }

  add_action(
    'login_header',
    function () use ( $template_path ) {
      load_template( $template_path, true );
    }
  );
}

\add_action(
  'admin_enqueue_scripts',
  function ($hook_suffix) {
    \wp_enqueue_style(
      'ionos-security-credentials-checking',
      \plugins_url('style.css', __FILE__),
      [],
      filemtime(PLUGIN_DIR . '/inc/security/style.css')
    );
  }
);

function validate_password_reset( $errors, $user ) {
  $pass1 = filter_input( INPUT_POST, 'pass1' );
  $pass2 = filter_input( INPUT_POST, 'pass2' );

  if ( empty( $pass1 ) || $pass1 !== $pass2 ) {
    return;
  }

  if ( true === is_leaked( '', $pass1 ) ) {
    $errors->add( 'password_leaked', __( 'The entered password has already been leaked. Please choose another one.', 'ionos-security' ) );
  } else {
    update_user_meta( $user->ID, LEAKED_CREDENTIALS_FLAG_NAME, false );
  }
}

function authenticate( $user, $username, $password ) {
  if ( is_wp_error( $user ) || $user === null || empty( $password ) ) {
    return $user;
  }

  $is_password_leaked = has_leaked_flag( $user->ID );

  // Don't check for a leak, on a flagged account.
  if ( $is_password_leaked === false ) {
    $is_password_leaked = is_leaked( $user, $password );
    update_user_meta( $user->ID, LEAKED_CREDENTIALS_FLAG_NAME, $is_password_leaked );
  }

  if ( is_valid_email( $user->user_email ) === false || $is_password_leaked === false ) {
    return $user;
  }

  add_filter( 'ionos_login_redirect_to', [ __CLASS__, 'redirect_to_leaked_notice' ], 200 );
  return new WP_Error( 'ionos_password_leaked', __( 'It looks like your password has been compromised. To protect the security of your account, it‘s crucial that you change your password immediately. This will ensure that your personal and sensitive information remains safe and secure. An email was sent to your email address. Please follow the instruction to reset your password.', 'ionos-security' ) );
}

function is_valid_email( $email ) {
  return ! is_system_email( $email ) && is_email( $email );
}

function is_system_email( $email ) {
  return $email === 'no-reply@wpservice.io';
}

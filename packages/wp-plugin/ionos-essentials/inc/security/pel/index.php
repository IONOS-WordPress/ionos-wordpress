<?php

namespace ionos\essentials\security\pel;

// exit if accessed directly
if (! defined('ABSPATH')) {
  exit();
}

add_action( 'login_init', function () {
  if ( ! \get_option( 'ionos_security_pel_enabled', false ) ) {
    return;
  }

  remove_filter( 'authenticate', 'wp_authenticate_email_password', 20 );
  add_filter( 'authenticate',function ( $user, $username ) {
    if ( false !== strpos( $username, '@' ) ) {
      return new \WP_Error(
        'email_login_inactive',
        __( '<strong>Error</strong>: The login with an email address is deactivated for this website. Please use your username instead.', 'ionos-security' )
      );
    }

    return $user;
  }, 200, 2 );
});

// Add the menu item to the settings page
add_filter('ionos_essentials_security_menu_item', function ($menu) {
  $menu[] = [
    'title' => __('Login Protection', 'ionos-essentials'),
    'tab'   => 'login-protection',
  ];

  return $menu;
}, 40, 1);

add_action('implement_security_feature_page', function () {
  global $current_screen;
  if (( strpos($current_screen->id, '_page_ionos_security') === false ) || !isset( $_GET['tab'] ) || ( isset( $_GET['tab'] ) && $_GET['tab'] !== 'login-protection' )) {
    return;
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $value = 0;
    if ( isset($_POST['ionos_security_pel_enabled']) ) {
      $value = 1;
    }

    \update_option('ionos_security_pel_enabled', $value);
  }

  $is_enabled = \get_option('ionos_security_pel_enabled', false);

  echo '
    <form method="POST">
      <label>
        <input type="checkbox" name="ionos_security_pel_enabled" value="1" ' . checked($is_enabled, true, false) . '>
        ' . __('Enable PEL Security', 'ionos-essentials') . '
      </label>
      <br><br>
      <button type="submit" class="button button-primary">' . __('Save', 'ionos-essentials') . '</button>
    </form>';
});

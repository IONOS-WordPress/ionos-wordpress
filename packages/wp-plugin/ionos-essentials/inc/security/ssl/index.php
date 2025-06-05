<?php

namespace ionos\essentials\security\ssl;

// exit if accessed directly
if (! defined('ABSPATH')) {
  exit();
}

// Add the menu item to the settings page
add_filter('ionos_essentials_security_menu_item', function ($menu) {
  $menu[] = [
    'title' => __('SSL', 'ionos-essentials'),
    'tab'   => 'ssl-check',
  ];

  return $menu;
}, 20, 1);

add_action( 'admin_enqueue_scripts', function() {
  if ( get_transient( 'ionos-ssl-check-notice-dismissed' ) ) {
    return;
  }
  wp_enqueue_script(
    'ionos-ssl-check',
    plugins_url( 'script.js', __FILE__ ),
    [],
    [],
    true
  );

  $vars = [
    'ajax_url' => admin_url( 'admin-ajax.php' ),
  ];
  wp_localize_script( 'ionos-ssl-check', 'ionosSSLCheck', $vars );
} );

add_action('implement_security_feature_page', function () {
  global $current_screen;
  if (( strpos($current_screen->id, '_page_ionos_security') === false ) || !isset( $_GET['tab'] )|| ( isset( $_GET['tab'] ) && $_GET['tab'] !== 'ssl-check' )) {
    return;
  }

  $label = esc_html__( 'Current SSL-Status', 'ionos-security' );
  $headline = esc_html__( 'SSL', 'ionos-security' );

  if ( is_ssl() ) {
    $description = __( 'Your WordPress website is currently <strong>with SSL</strong>, which means that the connection between your website and users\' browsers is encrypted. We will warn you if your website should no longer have SSL.', 'ionos-security' );
    $status      = esc_html__( 'Secure', 'ionos-security' );
  } else {
    $description  = __( 'Your WordPress website is currently <strong>without SSL</strong>, which means that the connection between your website and users\' browsers is not encrypted. It is highly <strong>recommended to activate SSL</strong> to protect sensitive information and to provide a secure browsing.', 'ionos-security' );
    $description .= sprintf(
      '<p><a href="%s" class="button" target="_blank">%s</a></p>',
      esc_url( __( 'https://ionos.com/help', 'ionos-security' ) ),
      esc_html__( 'Learn more about SSL and how to activate it.', 'ionos-security' )
    );
    $status       = esc_html__( 'Insecure', 'ionos-security' );
  }

  echo '<form action="' . admin_url('option.php') . '" method="POST">' .
       sprintf( '<h2 class="headline biggerh2">%s</h2><p><strong>%s</strong>: %s</p><p>%s</p>', $headline, $label, $status, $description ) .
       '</form>';
});

add_action( 'admin_notices', function() {
  if ( is_ssl() || get_transient( 'ionos-ssl-check-notice-dismissed' ) ) {
    return;
  }

  $notice = __( 'Your WordPress website is currently <strong>without SSL</strong>, which means that the connection between your website and users\' browsers is not encrypted. It is highly <strong>recommended to activate SSL</strong> to protect sensitive information and to provide a secure browsing.', 'ionos-security' );
  $button = sprintf(
    '<a href="%s" class="button" target="_blank">%s</a>',
    esc_url( __( 'https://ionos.com/help', 'ionos-security' ) ),
    esc_html__( 'Learn more about SSL and how to activate it.', 'ionos-security' )
  );
  printf(
    '<div class="ionos-ssl-check notice notice-warning is-dismissible"><p>%s<br>%s</p></div>',
    wp_kses(
      $notice,
      [
        'strong' => [],
        'em'     => [],
      ]
    ),
    $button // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
  );
} );

add_action( 'wp_ajax_ionos-ssl-check-dismiss-notice', function() {
  set_transient( 'ionos-ssl-check-notice-dismissed', true, 0 );
} );

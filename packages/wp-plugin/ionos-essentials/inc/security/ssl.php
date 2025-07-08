<?php

namespace ionos\essentials\security;

// exit if accessed directly
if (! defined('ABSPATH')) {
  exit();
}

if ( ! get_transient( 'ionos-ssl-check-notice-dismissed' ) ) {
  add_action( 'admin_notices', [ __CLASS__, 'admin_notice' ] );
  // add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_script' ] );
  add_action( 'wp_ajax_ionos-ssl-check-dismiss-notice', [ __CLASS__, 'dismiss_notice' ] );
}


/* function enqueue_script() {
  $assets = include_once IONOS_SECURITY_DIR . '/build/ssl-check.asset.php';
  wp_enqueue_script(
    self::HANDLE,
    plugins_url( 'build/ssl-check.js', IONOS_SECURITY_FILE ),
    $assets['dependencies'],
    $assets['version'],
    true
  );

  $vars = [
    'ajax_url' => admin_url( 'admin-ajax.php' ),
  ];
  wp_localize_script( self::HANDLE, 'ionosSSLCheck', $vars );
} */

function admin_notice() {
  if ( is_ssl() ) {
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
}

function dismiss_notice() {
  set_transient( 'ionos-ssl-check-notice-dismissed', true, 0 );
}

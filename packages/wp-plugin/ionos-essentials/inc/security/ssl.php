<?php

namespace ionos\essentials\security;

// exit if accessed directly
if (! defined('ABSPATH')) {
  exit();
}

const IONOS_SSL_CHECK_NOTICE_DISMISSED = 'ionos-ssl-check-notice-dismissed';

if (! \get_transient(IONOS_SSL_CHECK_NOTICE_DISMISSED)) {
  \add_action('admin_notices', function () {
    if (is_ssl()) {
      return;
    }

    $notice = __('Your WordPress website is currently <strong>without SSL</strong>, which means that the connection between your website and users\' browsers is not encrypted. It is highly <strong>recommended to activate SSL</strong> to protect sensitive information and to provide a secure browsing.', 'ionos-security');
    $button = sprintf(
      '<a href="%s" class="button" target="_blank">%s</a>',
      \esc_url(__('https://ionos.com/help', 'ionos-security')),
      \esc_html__('Learn more about SSL and how to activate it.', 'ionos-security')
    );
    printf(
      '<div class="ionos-ssl-check notice notice-warning is-dismissible"><p>%s<br>%s</p></div>',
      \wp_kses($notice, [
        'strong' => [],
        'em'     => [],
      ]),
      $button
    );
  });

  \add_action(
    'wp_ajax_ionos-ssl-check-dismiss-notice',
    fn () => \set_transient(IONOS_SSL_CHECK_NOTICE_DISMISSED, true, 0)
  );
}

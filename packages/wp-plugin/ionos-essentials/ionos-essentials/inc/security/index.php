<?php

namespace ionos\essentials\security;

use ionos\essentials\Tenant;

defined('ABSPATH') || exit();

const IONOS_SECURITY_FEATURE_OPTION                      = 'IONOS_SECURITY_FEATURE_OPTION';
const IONOS_SECURITY_FEATURE_OPTION_XMLRPC               = 'IONOS_SECURITY_FEATURE_OPTION_XMLRPC';
const IONOS_SECURITY_FEATURE_OPTION_PEL                  = 'IONOS_SECURITY_FEATURE_OPTION_PEL';
const IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING = 'IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING';
const IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY          = 'IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY';

const IONOS_SECURITY_FEATURE_OPTION_DEFAULT = [
  IONOS_SECURITY_FEATURE_OPTION_XMLRPC               => true,
  IONOS_SECURITY_FEATURE_OPTION_PEL                  => true,
  IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING => true,
  IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY          => true,
];

\add_action('init', function () {
  $security_options = \get_option(IONOS_SECURITY_FEATURE_OPTION, IONOS_SECURITY_FEATURE_OPTION_DEFAULT);

  // ensure options are an array
  if (! is_array($security_options)) {
    $security_options = IONOS_SECURITY_FEATURE_OPTION_DEFAULT;
  }

  // merge defaults with existing options
  $_security_options = array_merge($security_options, IONOS_SECURITY_FEATURE_OPTION_DEFAULT);

  // if the array keys differ from defaults, persist the updated options
  if (0 < count(array_diff_key($security_options, $_security_options))) {
    \update_option(IONOS_SECURITY_FEATURE_OPTION, $_security_options);
    $security_options = $_security_options;
  }

  require_once __DIR__ . '/ssl.php';
  if (! defined('IONOS_IS_STRETCH')) {
    if (true === $security_options[IONOS_SECURITY_FEATURE_OPTION_XMLRPC]) {
      require_once __DIR__ . '/xmlrpc.php';
    }
  }
  if (true === $security_options[IONOS_SECURITY_FEATURE_OPTION_PEL]) {
    require_once __DIR__ . '/pel.php';
  }
  if (true === $security_options[IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING]) {
    require_once __DIR__ . '/credentials-checking.php';
  }
});

\add_action('admin_enqueue_scripts', function () {
  \wp_localize_script('ionos-essentials-security', 'ionosSecurityWpData', [
    'nonce'              => \wp_create_nonce('wp_rest'),
    'ajaxUrl'            => admin_url('admin-ajax.php'),
  ]);
});

if (\get_transient('ionos_security_migrated_notice_show')) {

  \add_action('admin_notices', function () {
    // do not show notice on our dashboard
    $current_screen = \get_current_screen();
    $brand          = Tenant::get_slug();
    if (! isset($current_screen->id) || in_array($current_screen->id, ['toplevel_page_' . $brand], true)) {
      return;
    }

    $notice = sprintf(
      /* translators: %s: URL to the Hub's Tools & Security page. */
      __('The former Security plugin is now part of the new essentials plugin. You can find all functionality under <a href="%s">Tools & Security</a> of our new Hub.', 'ionos-essentials'),
      \esc_url(\admin_url() . '?page=' . Tenant::get_slug() . '#tools')
    );

    printf(
      '<div class="ionos-security-migrated notice notice-warning is-dismissible"><p>%s</p></div>',
      \wp_kses($notice, [
        'a' => [
          'href' => [],
        ],
      ]),
    );
  });

  \add_action(
    'wp_ajax_ionos-security-migrated-notice',
    fn () => (\delete_transient('ionos_security_migrated_notice_show') && \wp_die())
  );
}

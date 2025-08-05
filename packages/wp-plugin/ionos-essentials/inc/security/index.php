<?php

namespace ionos\essentials\security;

use ionos\essentials\Tenant;

use function ionos\essentials\is_stretch;
use const ionos\essentials\PLUGIN_DIR;
use const ionos\essentials\PLUGIN_FILE;

// exit if accessed directly
if (! defined('ABSPATH')) {
  exit();
}

const IONOS_SECURITY_FEATURE_OPTION                      = 'IONOS_SECURITY_FEATURE_OPTION';
const IONOS_SECURITY_FEATURE_OPTION_XMLRPC               = 'IONOS_SECURITY_FEATURE_OPTION_XMLRPC';
const IONOS_SECURITY_FEATURE_OPTION_PEL                  = 'IONOS_SECURITY_FEATURE_OPTION_PEL';
const IONOS_SECURITY_FEATURE_OPTION_SSL                  = 'IONOS_SECURITY_FEATURE_OPTION_SSL';
const IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING = 'IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING';
const IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY          = 'IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY';

const IONOS_SECURITY_FEATURE_OPTION_DEFAULT = [
  IONOS_SECURITY_FEATURE_OPTION_XMLRPC               => true,
  IONOS_SECURITY_FEATURE_OPTION_PEL                  => true,
  IONOS_SECURITY_FEATURE_OPTION_SSL                  => true,
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

  if (! is_stretch()) {
    if (true === $security_options[IONOS_SECURITY_FEATURE_OPTION_XMLRPC]) {
      require_once __DIR__ . '/xmlrpc.php';
    }
  }
  if (true === $security_options[IONOS_SECURITY_FEATURE_OPTION_PEL]) {
    require_once __DIR__ . '/pel.php';
  }
  if (true === $security_options[IONOS_SECURITY_FEATURE_OPTION_SSL]) {
    require_once __DIR__ . '/ssl.php';
  }
  if (true === $security_options[IONOS_SECURITY_FEATURE_OPTION_CREDENTIALS_CHECKING]) {
    require_once __DIR__ . '/credentials-checking.php';
  }
  // if (!empty($security_options[IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY]) AND true === $security_options[IONOS_SECURITY_FEATURE_OPTION_MAIL_NOTIFY]) {
  //   require_once __DIR__ . '/vulnerability-scan.php';
  // }
});

\add_action('admin_enqueue_scripts', function () {
  wp_enqueue_script(
    'ionos-security-js',
    plugins_url('inc/security/security.js', PLUGIN_FILE),
    [],
    filemtime(PLUGIN_DIR . '/inc/security/security.js'),
    true
  );

  wp_localize_script('ionos-security-js', 'ionosSecurityWpData', [
    'nonce'              => wp_create_nonce('wp_rest'),
    'ajaxUrl'            => admin_url('admin-ajax.php'),
  ]);
});

if (\get_transient('ionos_security_migrated_notice_show')) {

  \add_action('admin_notices', function () {
    // do not show notice on our dashboard
    $current_screen = \get_current_screen();
    $brand          = Tenant::get_instance()->name;
    if (! isset($current_screen->id) || in_array($current_screen->id, ['toplevel_page_' . $brand], true)) {
      return;
    }

    $notice = sprintf(
      __('The former Security plugin is now part of the new essentials plugin. You can find all functionality under <a href="%s">Tools & Security</a> of our new Hub.', 'ionos-security'),
      \esc_url(\admin_url() . '?page=' . Tenant::get_instance()->name . '#tools')
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
    fn () => (\delete_transient('ionos_security_migrated_notice_show') && wp_die())
  );
}

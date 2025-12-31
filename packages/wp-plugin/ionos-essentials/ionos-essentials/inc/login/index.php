<?php

namespace ionos\essentials\login;

use ionos\essentials\Tenant;
use const ionos\essentials\PLUGIN_FILE;

defined('ABSPATH') || exit();

\add_action('init', function () {
  if (false === in_array($GLOBALS['pagenow'], ['wp-login.php', 'wp-register.php'], true)) {
    return;
  }

  \add_action(
    'login_enqueue_scripts',
    function () {
      \wp_enqueue_style(
        'ionos-login-redesign',
        \plugin_dir_url(__FILE__) . 'style.css',
        [],
        filemtime(\plugin_dir_path(__FILE__) . 'style.css')
      );

      // Enqueue script for SSO button click tracking
      \wp_enqueue_script(
        'ionos-login-tracking',
        \plugin_dir_url(__FILE__) . 'script.js',
        [],
        filemtime(\plugin_dir_path(__FILE__) . 'script.js'),
        true
      );

      // Pass REST API URL to JavaScript
      \wp_localize_script('ionos-login-tracking', 'ionosLoginTracking', [
        'restUrl' => \rest_url('ionos/essentials/loop/v1/sso-click'),
        'nonce'   => \wp_create_nonce('wp_rest'),
      ]);
    }
  );

  \add_filter('login_body_class', function ($classes) {
    $classes[] = 'ionos-group-page';

    return $classes;
  });

  \add_action(
    'login_header',
    function () {
      global $interim_login;
      if ($interim_login) {
        return;
      }

      printf(
        <<<EOF
        <section class="header">
          <img
            src="%s"
            alt="%s"
            class="logo"
          >
        </section>
EOF
        ,
        \esc_attr(
          \plugins_url('ionos-essentials/inc/dashboard/data/tenant-logos/' . Tenant::get_slug() . '.svg', PLUGIN_FILE)
        ),
        \esc_attr(Tenant::get_label())
      );
    }
  );
});

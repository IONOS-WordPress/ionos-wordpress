<?php

namespace ionos\essentials\login;

use const ionos\essentials\PLUGIN_DIR;
use const ionos\essentials\PLUGIN_FILE;
use ionos\essentials\Tenant;

defined('ABSPATH') || exit();

\add_action('init', function () {
  if (false === in_array($GLOBALS['pagenow'], ['wp-login.php', 'wp-register.php'], true)) {
    return;
  }

  \add_action(
    'login_enqueue_scripts',
    function () {
      $assets  = require_once PLUGIN_DIR . '/ionos-essentials/build/login/index.asset.php';
      $src_url = \plugins_url('ionos-essentials/build/login/', PLUGIN_FILE);

      \wp_enqueue_style('ionos-login-redesign', $src_url . 'index.css', [], $assets['version']);

      \wp_enqueue_script(
        'ionos-login-tracking',
        $src_url . 'index.js',
        $assets['dependencies'],
        $assets['version'],
        true
      );
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

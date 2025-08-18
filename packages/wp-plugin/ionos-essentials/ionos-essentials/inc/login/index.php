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
        \plugins_url('style.css', __FILE__),
        [],
        filemtime(__DIR__ . '/style.css')
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
        \esc_attr(\plugins_url('/ionos-essentials/inc/dashboard/data/tenant-logos/' . Tenant::get_slug() . '.svg', PLUGIN_FILE)),
        \esc_attr(Tenant::get_label())
      );
    }
  );
});

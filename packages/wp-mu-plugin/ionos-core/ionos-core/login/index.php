<?php

namespace ionos\ionos_core\login;

use ionos\essentials\Tenant;
use const ionos\ionos_core\PLUGIN_DIR;
use const ionos\ionos_core\PLUGIN_FILE;

defined('ABSPATH') || exit();

// Skip loading if ionos-core's login has already loaded
if (defined('IONOS_LOGIN_LOADED')) {
  return;
}

\add_action('init', function () {
  if (false === in_array($GLOBALS['pagenow'], ['wp-login.php', 'wp-register.php'], true)) {
    return;
  }

  \add_action(
    'login_enqueue_scripts',
    function () {
      $assets  = require_once PLUGIN_DIR . '/ionos-core/build/login/index.asset.php';
      $src_url = \plugins_url('ionos-core/build/login/', PLUGIN_FILE);

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
          \plugins_url('ionos-core/login/assets/tenant-logos/' . Tenant::get_slug() . '.svg', PLUGIN_FILE)
        ),
        \esc_attr(Tenant::get_label())
      );
    }
  );
});

define('IONOS_LOGIN_LOADED', true);

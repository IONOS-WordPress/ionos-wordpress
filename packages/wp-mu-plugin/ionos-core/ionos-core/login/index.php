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
      $assets_file = PLUGIN_DIR . '/ionos-core/build/login/index.asset.php';

      if (! file_exists($assets_file)) {
        return;
      }

      $assets  = require_once $assets_file;
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

      $essentials_file = WP_PLUGIN_DIR . '/ionos-essentials/ionos-essentials.php';
      if (! file_exists($essentials_file)) {
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
          \plugins_url('ionos-essentials/ionos-essentials/inc/dashboard/data/tenant-logos/' . Tenant::get_slug() . '.svg', $essentials_file)
        ),
        \esc_attr(Tenant::get_label())
      );
    }
  );
});

define('IONOS_LOGIN_LOADED', true);

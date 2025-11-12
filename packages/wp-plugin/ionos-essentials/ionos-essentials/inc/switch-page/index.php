<?php

namespace ionos\essentials\switch_page;

use ionos\essentials\Tenant;

defined('ABSPATH') || exit();

/**
 * Add onboarding menu page.
 */
\add_action(
  'admin_menu',
  function () {
    // submenu_page parent dashboard.
    \add_submenu_page(
      false, //for test 'ionos-essentials-dashboard',
      'Assistant',
      'Assistant',
      'manage_options',
      Tenant::get_slug() . '-onboarding',
      fn () => \load_template(__DIR__ . '/view.php')
    );
    \remove_menu_page('extendify-assist');
  },
  100,
  1
);

/**
 * Redirects to extendify-launch are caught.
 * We send the user to the ai-switch-page
 */
\add_filter(
  'wp_redirect',
  function ($location) {
    // extendify-launch always opens switch page
    if (\admin_url('admin.php?page=extendify-launch') === $location) {
      return \admin_url('admin.php?page=' . Tenant::get_slug() . '-onboarding');
    }
    // wp-admin and extendify dashboard redirect to our dashboard OR the default wp-admin dashboard
    $redirects = ['wp-admin/', admin_url(), \admin_url('admin.php?page=extendify-assist')];
    if (in_array($location, $redirects)) {
      $show_ionos_dashboard = (\get_option('ionos_essentials_dashboard_mode', true));
      return $show_ionos_dashboard ? \admin_url('admin.php?page=' . Tenant::get_slug()) : \admin_url();
    }
    return $location;
  },
  10000,
  1
);

\add_action(
  'load-toplevel_page_extendify-assist',
  fn () => \wp_safe_redirect(\admin_url('admin.php?page=' . Tenant::get_slug()))
);

\add_filter(
  'admin_title',
  function ($title) {
    if (isset($_GET['page']) && Tenant::get_slug() . '-onboarding' === $_GET['page']) {
      return Tenant::get_label() . ' ' . __('Onboarding', 'ionos-essentials');
    }
    return $title;
  },
  11
);

\add_action(
  'admin_enqueue_scripts',
  function ($hook_suffix) {
    if (! str_contains($hook_suffix, '_page_' . Tenant::get_slug() . '-onboarding')) {
      return;
    }
    \wp_enqueue_style(
      'ionos-assistant-switch-page',
      \plugin_dir_url(__FILE__) . 'style.css',
      [],
      filemtime(\plugin_dir_path(__FILE__) . 'style.css')
    );

    wp_deregister_style('buttons');
  }
);

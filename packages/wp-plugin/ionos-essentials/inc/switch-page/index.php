<?php

namespace ionos\essentials\switch_page;

use const ionos\essentials\PLUGIN_DIR;

// exit if accessed directly
if (! defined('ABSPATH')) {
  exit();
}

function get_brand_lowercase(): string
{
  return strtolower(\get_option('ionos_group_brand', 'ionos'));
}

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
      get_brand_lowercase() . '-onboarding',
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
    if (\admin_url('admin.php?page=extendify-launch') === $location) {
      return \admin_url('admin.php?page=' . get_brand_lowercase() . '-onboarding');
    } elseif (\admin_url('admin.php?page=extendify-assist') === $location) {
      return \admin_url('admin.php?page=' . strtolower(\get_option('ionos_group_brand_menu', 'ionos')));
    }

    return $location;
  },
  10000,
  1
);

\add_action(
  'load-toplevel_page_extendify-assist',
  fn () => \wp_safe_redirect(\admin_url('admin.php?page=' . strtolower(\get_option('ionos_group_brand_menu', 'ionos'))))
);

\add_filter(
  'admin_title',
  function ($title) {
    if (isset($_GET['page']) && get_brand_lowercase() . '-onboarding' === $_GET['page']) {
      return \get_option('ionos_group_brand_menu', 'IONOS') . ' ' . __('Onboarding', 'ionos-essentials');
    }
    return $title;
  },
  11
);

\add_action(
  'admin_enqueue_scripts',
  function ($hook_suffix) {
    if (! str_contains($hook_suffix, '_page_' . get_brand_lowercase() . '-onboarding')) {
      return;
    }
    \wp_enqueue_style(
      'ionos-assistant-switch-page',
      \plugins_url('style.css', __FILE__),
      [],
      filemtime(PLUGIN_DIR . '/inc/switch-page/style.css')
    );
  }
);

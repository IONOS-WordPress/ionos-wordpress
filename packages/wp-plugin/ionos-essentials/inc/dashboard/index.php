<?php

namespace ionos\essentials\dashboard;

use const ionos\essentials\PLUGIN_DIR;

// exit if accessed directly
if (! defined('ABSPATH')) {
  exit();
}

const REQUIRED_USER_CAPABILITIES = 'read';

\add_action('init', function () {
  define('IONOS_ESSENTIALS_DASHBOARD_ADMIN_PAGE_TITLE', \get_option('ionos_group_brand_menu', 'IONOS'));
  define('ADMIN_PAGE_SLUG', strtolower(\get_option('ionos_group_brand_menu', 'IONOS')));
  define('ADMIN_PAGE_HOOK', 'toplevel_page_' . ADMIN_PAGE_SLUG);
});

\add_action('admin_menu', function () {
  $tenant_name = \get_option('ionos_group_brand', 'ionos');
  $tenant_icon = '';

  $file_path = __DIR__ . "/data/tenant-icons/{$tenant_name}.svg";
  if (file_exists($file_path)) {
    $svg         = file_get_contents($file_path);
    $tenant_icon = 'data:image/svg+xml;base64,' . base64_encode($svg);
  }

  \add_menu_page(
    page_title : IONOS_ESSENTIALS_DASHBOARD_ADMIN_PAGE_TITLE,
    menu_title : IONOS_ESSENTIALS_DASHBOARD_ADMIN_PAGE_TITLE,
    capability : REQUIRED_USER_CAPABILITIES,
    menu_slug  : ADMIN_PAGE_SLUG,
    icon_url   : $tenant_icon,
    position: 1,
    // no callback because submenu page renders content
  );

  // add submenu with same menu_slug as parent so that title of sub is different
  \add_submenu_page(
    parent_slug: ADMIN_PAGE_SLUG,
    page_title : __('Overview', 'ionos-essentials'),
    menu_title : __('Overview', 'ionos-essentials'),
    capability : REQUIRED_USER_CAPABILITIES,
    menu_slug  : ADMIN_PAGE_SLUG,
    callback   : function () {
      require_once PLUGIN_DIR . '/inc/dashboard/view.php';
    },
  );

  // we stop ionos-library from removing our submenu item
  add_action('admin_menu', function () {
    global $wp_filter;
    // ionos-library uses a priority of 999 to remove the submenu item
    if (isset($wp_filter['admin_menu']->callbacks[999])) {
      foreach ($wp_filter['admin_menu']->callbacks[999] as $callback) {
        if (is_array($callback['function']) && 'remove_unwanted_submenu_item' === $callback['function'][1]) {
          remove_action('admin_menu', $callback['function'], 999);
        }
      }
    }
  });
}, 1);

// we want to be presented as "default page" in wp-admin
// redirect to our custom dashboard page if /wp-admin/ is requested
\add_action('load-index.php', function () {
  if (\current_user_can(REQUIRED_USER_CAPABILITIES)) {
    $current_url = \home_url($_SERVER['REQUEST_URI']);
    $admin_url   = \get_admin_url();

    if ($current_url !== $admin_url) { // only redirect if we are on empty /wp-admin/
      return;
    }

    \wp_safe_redirect(\menu_page_url(ADMIN_PAGE_SLUG, false));
  }
});

// fixes the displayed page title for our custom admin page.
\add_filter(
  hook_name: 'admin_title',
  callback: function ($admin_title, $title) {
    if (ADMIN_PAGE_HOOK === \get_current_screen()?->id) {
      return IONOS_ESSENTIALS_DASHBOARD_ADMIN_PAGE_TITLE;
    }
    return $admin_title;
  },
  accepted_args : 2
);

\add_action('admin_enqueue_scripts', function ($hook) {
  \wp_add_inline_style('admin-bar', '[data-extendify-tour-id=site-assistant-tour] {
      display: none;
    }');
});

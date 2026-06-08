<?php

namespace ionos\essentials\maintenance_mode;

defined('ABSPATH') || exit();

use ionos\essentials\Tenant;

function is_maintenance_mode()
{
  return \get_option('ionos_essentials_maintenance_mode', false);
}

\add_action('admin_bar_menu', function ($wp_admin_bar) {
  $brand = Tenant::get_slug();

  $args = [
    'id'    => 'ionos_maintenance_mode',
    'title' => \esc_html('Maintenance page active', 'ionos-essentials'),
    'href'  => \admin_url('admin.php?page=' . $brand . '#tools'),
    'meta'  => [
      'class' => 'ionos-maintenance-mode ionos-maintenance-only',
      'title' => \esc_attr('Maintenance page active', 'ionos-essentials'),
    ],
  ];
  $wp_admin_bar->add_node($args);

  \wp_enqueue_style(
    'ionos-maintenance-mode-admin',
    \plugin_dir_url(__FILE__) . 'maintenance.css',
    [],
    filemtime(plugin_dir_path(__FILE__) . 'maintenance.css')
  );
}, 31);

add_action('init', function () {
  if (! is_maintenance_mode()) {
    return;
  }

  if (\is_user_logged_in()) {
    return;
  }

  $request_uri  = $_SERVER['REQUEST_URI'] ?? '';
  $request_path = trim((string) parse_url($request_uri, PHP_URL_PATH), '/\\');
  $home_path    = trim((string) parse_url(home_url('/'), PHP_URL_PATH), '/\\');

  if ('' !== $home_path && ($request_path === $home_path || str_starts_with($request_path, $home_path . '/'))) {
    $site_request_path = ltrim(substr($request_path, strlen($home_path)), '/\\');
  } else {
    $site_request_path = $request_path;
  }

  $rest_route = isset($_GET['rest_route']) ? trim((string) \wp_unslash($_GET['rest_route']), '/\\') : '';
  $rest_prefix = trim((string) \rest_get_url_prefix(), '/\\');
  $is_rest_request = '' !== $rest_route || $site_request_path === $rest_prefix || str_starts_with($site_request_path, $rest_prefix . '/');

  if ('wp-login.php' === $GLOBALS['pagenow'] || 'wp-admin' === $site_request_path || str_starts_with($site_request_path, 'wp-admin/')) {
    return;
  }

  if (
    (defined('DOING_AJAX') && DOING_AJAX) ||
    (defined('WP_CLI')     && WP_CLI)     ||
    $is_rest_request
  ) {
    return;
  }

  if (isset($_GET['ionos_maintenance_mode']) || get_query_var('ionos_maintenance_mode') || 'maintenance' === $site_request_path) {
    readfile(plugin_dir_path(__FILE__) . 'assets/maintenance.html');
    exit;
  }

  \add_rewrite_rule('maintenance/?$', 'index.php?ionos_maintenance_mode=1', 'top');
  if ('maintenance' === $site_request_path) {
    return;
  }
  global $wp_rewrite;
  if ($wp_rewrite->using_permalinks()) {
    \wp_redirect(home_url('/maintenance'), 302);
    exit;
  }
  \wp_redirect('index.php?ionos_maintenance_mode=1', 302);
  exit;
});

add_filter('admin_body_class', function ($classes) {
  if (is_maintenance_mode()) {
    $classes .= ' ionos-maintenance-mode';
  }
  return $classes;
});

add_filter('body_class', function ($classes) {
  if (is_maintenance_mode()) {
    $classes[] = 'ionos-maintenance-mode';
  }
  return $classes;
});

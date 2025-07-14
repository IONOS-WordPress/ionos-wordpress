<?php

namespace ionos\essentials\maintenance_mode;

function is_maintenance_mode()
{
  return \get_option('ionos_essentials_maintenance_mode', false);
}

add_action('admin_bar_menu', function ($wp_admin_bar) {
  $args = [
    'id'    => 'ionos_maintenance_mode',
    'title' => \esc_html('Maintenance page active', 'ionos-essentials'),
    'href'  => \admin_url('admin.php?page=ionos#tools'),
    'meta'  => [
      'class' => 'ionos-maintenance-mode ionos-maintenance-only',
      'title' => \esc_attr('Maintenance page active', 'ionos-essentials'),
    ],
  ];
  $wp_admin_bar->add_node($args);
}, 31);

add_action('admin_enqueue_scripts', function () {
  wp_enqueue_script(
    'ionos-maintenance-mode-admin',
    plugin_dir_url(__FILE__) . 'maintenance.js',
    ['jquery'],
    filemtime(plugin_dir_path(__FILE__) . 'maintenance.js'),
    true
  );
});

add_action('init', function () {
  if (! is_maintenance_mode()) {
    return;
  }

  if (\is_user_logged_in()) {
    return;
  }

  if ('wp-login.php' === $GLOBALS['pagenow'] || str_starts_with($_SERVER['REQUEST_URI'], '/wp-admin')) {
    return;
  }

  if (
    (defined('DOING_AJAX') && DOING_AJAX) ||
    (defined('WP_CLI')     && WP_CLI)
  ) {
    return;
  }

  wp_redirect(plugin_dir_url(__FILE__) . 'assets/maintenance.html');
  exit;
});

add_filter('admin_body_class', function ($classes) {
  if (is_maintenance_mode()) {
    $classes .= ' ionos-maintenance-mode';
  }
  return $classes;
});

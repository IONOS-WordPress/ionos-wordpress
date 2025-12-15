<?php

namespace ionos\essentials\adminbar;

use function ionos\essentials\tenant\get_tenant_config;


function add_admin_bar_menu() {
  global $wp_admin_bar;

  if (!is_admin_bar_showing() || !current_user_can('manage_options')) {
    return;
  }

  $data = get_tenant_config();
  if (empty($data) || empty($data['domain']) || empty($data['banner_links']['managehosting'])) {
    return;
  }

  $parent = ($wp_admin_bar->get_node('appearance') ? 'appearance' : 'site-name');

  $args = array(
      'id'     => 'ionos-essentials',
      'title'  => \esc_html__('Manage Hosting', 'ionos-essentials'),
      'href'   => $data['domain'] . $data['banner_links']['managehosting'],
      'parent' => $parent,
      'meta'   => array(
          'target' => '_blank',
      ),
  );
  $wp_admin_bar->add_node( $args );
}

add_action('admin_bar_menu', __NAMESPACE__ . '\add_admin_bar_menu', 100);

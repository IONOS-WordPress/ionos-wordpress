<?php

namespace ionos\essentials\security\xmlrpc;

// exit if accessed directly
if (! defined('ABSPATH')) {
  exit();
}

// Add the menu item to the settings page
add_filter('ionos_essentials_security_menu_item', function ($menu) {
  $menu[] = [
    'title' => __('XML-RPC', 'ionos-essentials'),
    'tab'   => 'xmlrpc-guard',
  ];

  return $menu;
}, 30, 1);

add_action('implement_security_feature_page', function () {
  global $current_screen;
  if (( strpos($current_screen->id, '_page_ionos_security') === false ) || !isset( $_GET['tab'] ) || ( isset( $_GET['tab'] ) && $_GET['tab'] !== 'xmlrpc-guard' )) {
    return;
  }

  echo '<h1>' . __('XML-RPC', 'ionos-essentials') . '</h1>';
});

<?php

namespace ionos\essentials\security\wpscan;

// exit if accessed directly
if (! defined('ABSPATH')) {
  exit();
}

// Add the menu item to the settings page
add_filter('ionos_essentials_security_menu_item', function ($menu) {
  $menu[] = [
    'title' => __('Vulnerability Scan', 'ionos-essentials'),
    'tab'   => 'wpscan',
  ];

  return $menu;
}, 10, 1);

add_action('implement_security_feature_page', function () {
  global $current_screen;
  if (( strpos($current_screen->id, '_page_ionos_security') === false ) || ( isset( $_GET['tab'] ) && $_GET['tab'] !== 'wpscan' )) {
    return;
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $value = 0;
    if ( isset($_POST['ionos_security_pel_enabled']) ) {
      $value = 1;
    }

    \update_option('ionos_security_pel_enabled', $value);
  }

  $is_enabled = \get_option('ionos_security_pel_enabled', false);

  echo '<h1>' . __('Vulnerability Scan', 'ionos-essentials') . '</h1>';
});

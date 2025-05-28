<?php

namespace ionos\essentials\security;

// exit if accessed directly
use function ionos\essentials\security\pel\get_menu_page;

if ( ! defined( 'ABSPATH' ) ) {
  exit();
}

require_once __DIR__ . '/pel/index.php';
require_once __DIR__ . '/ssl/index.php';
require_once __DIR__ . '/wpscan/index.php';
require_once __DIR__ . '/xmlrpc/index.php';

add_action( 'current_screen', function ( $screen ) {

  if ( strpos( $screen->id, '_page_ionos_security' ) === false ) {
    return;
  }
} );

\add_action( 'admin_init', function () {

} );


\add_action( 'admin_enqueue_scripts', function () {
  \wp_enqueue_style( 'ionos-essentials-security', plugins_url( 'style.css', __FILE__ ) );
} );

// implement security feature menu item
\add_action( 'admin_menu', function () {
  $slug = strtolower( \get_option( 'ionos_group_brand_menu', 'IONOS' ) );
  \add_submenu_page(
    parent_slug: $slug,
    page_title: __( 'Security', 'ionos-essentials' ),
    menu_title: __( 'Security', 'ionos-essentials' ),
    capability: 'read',
    menu_slug: 'ionos_security',
    callback: function () {
      $menu_items = \apply_filters(
        'ionos_essentials_security_menu_item',
        []
      );

      $menu = '<ul class="filter-links">';
      foreach ( $menu_items as $key => $menu_item ) {
        $class = ( isset($_GET['tab']) && $_GET['tab'] === $menu_item['tab'] ) ? ' class="current" ' : ( !isset($_GET['tab']) && $key === 0 ? ' class="current" ' : '');
        $menu .=  '<li class="ionos-settings--' . $menu_item['tab'] . '"><a href="' . esc_url( admin_url('admin.php?page=ionos_security&tab=' . $menu_item['tab'] ) ) . '"' . $class . ' aria-current="page">' . esc_html( $menu_item['title'] ) . '</a></li>';
      }
      $menu .= '</ul>';

      echo '
        <div class="wrap">
          <h2 class="headline">' . __( 'Security', 'ionos-essentials' ) . '</h2>
          <div class="container">
              <div class="wp-filter">' . $menu . '</div>
              <span class="horizontal-line"></span>';
      do_action( 'implement_security_feature_page' );

      echo '</div></div>';
    }
  );
} );


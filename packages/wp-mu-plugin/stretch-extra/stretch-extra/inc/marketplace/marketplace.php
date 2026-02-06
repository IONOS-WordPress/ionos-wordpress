<?php

namespace ionos\stretch_extra\marketplace;

defined('ABSPATH') || exit();

if ( \get_option( 'ionos_group_brand' !== 'ionos' ) ) {
    return;
}

add_filter( 'install_plugins_tabs', function ( $tabs ) {
    unset( $tabs['featured'] );

    return array_merge(
			[ 'ionos' => 'IONOS ' . __('recommends', 'ionos-stretch-extra') ],
			$tabs
		);
} );

add_action( 'install_plugins_pre_ionos', function () {
  global $wp_list_table;

  $config = require_once __DIR__ . '/config.php';

  // 1. Define the plugin slugs you want
  $slugs = $config['wordpress_org_plugins'];

  // 2. Build an array of request definitions
  $field_query_string = '';
		foreach ( ['short_description','icons'] as $name => $value ) {
			$field_query_string .= "&fields[{$name}]={$value}";
		}

  $requests = [];
  foreach ($slugs as $slug) {
      $requests[] = [
          'url'  => "https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&slug=$slug{$field_query_string}",
          'type' => \WpOrg\Requests\Requests::GET,
          'data' => [
            'locale' => get_user_locale(),
          ],
      ];
  }

  // 3. Execute all requests simultaneously
  $responses = \WpOrg\Requests\Requests::request_multiple($requests);

  // 4. Process the data
  $plugins = [];
  foreach ($responses as $slug => $response) {
      if ($response instanceof \WpOrg\Requests\Response && $response->success) {
         $wp_list_table->items[] = json_decode($response->body, true);
      }
  }

  // 5. Sort items by slug
  usort($wp_list_table->items, fn($a, $b) => array_search($a['slug'], $slugs) <=> array_search($b['slug'], $slugs));

  // 6. Prepend IONOS Plugins
  $ionos_plupings = $config['ionos_plugins'];

  array_walk( $ionos_plupings, function ( &$plugin ) {
    $plugin['rating'] = 0;
    $plugin['ratings'] = [ '5' => 0, '4' => 0, '3' => 0, '2' => 0, '1' => 0 ];
    $plugin['num_ratings'] = 0;
    $plugin['active_installs'] = 0;
  } );


  $wp_list_table->items = array_merge(
    $ionos_plupings,
    $wp_list_table->items,
);

} );

add_action( 'install_plugins_ionos', function () {
    global $wp_list_table;

		$wp_list_table->set_pagination_args(
			[
				'total_items' => count( $wp_list_table->items ),
				'total_pages' => ceil(count( $wp_list_table->items ) / 10),
				'per_page'    => 10,
			]
		);

		display_plugins_table();
} );

add_action( 'admin_head-plugin-install.php', function () {
  echo '
    <style>
       div[class*="plugin-card-ionos-"],
       div.plugin-card-woocommerce-german-market-light {
          .column-downloaded, .column-rating {
            display: none;
          }
        }
    </style>';
} );

add_filter( 'plugins_api', function ( $result, $action, $args ) {
   	if ( $action !== 'plugin_information' ) {
			return $result;
		}

    $config = require_once __DIR__ . '/config.php';
    if ( ! in_array( $args->slug, array_keys( $config['ionos_plugins'] ), true ) ) {
      return $result;
    }

    $response = wp_remote_get( 'https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Flatest/ionos-essentials-info.json' );
    $pi       = json_decode( wp_remote_retrieve_body( $response ) );
    if ( ! is_object( $pi ) ) {
      return $result;
    }

    $pi->name          = 'Essentials';
    $pi->slug          = $args->slug;
    $pi->download_link = $pi->package;
    $pi->version       = $pi->version;
    $pi->requires      = '6.0';
    $pi->sections      = [
      _x( 'Description', 'Plugin installer section title' ) => __( $config['ionos_plugins']['ionos-essentials']['short_description'], 'ionos-stretch-extra' ),
      _x( 'Changelog', 'Plugin installer section title' )   => $pi->sections->changelog,
    ];

    return $pi;
}, 20, 3 );

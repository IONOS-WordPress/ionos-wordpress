<?php

namespace ionos\ionos_core;

function loop_data_response( \WP_REST_Request $request ): \WP_REST_Response {
	return new \WP_REST_Response( [
		'data' => [
      "foo" => "bar",
    ],
	] );
}

add_action( 'admin_notices', function() {
	echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'Welcome to IONOS Core', 'ionos-core' ) . '</p></div>';
} );

add_filter( 'rest_endpoints', function( array $endpoints ): array {
	$endpoints['/ionos/essentials/loop/v1/loop-data'] = [
		[
			'methods'             => [ 'GET' ],
			'callback'            => __NAMESPACE__ . '\loop_data_response',
			'permission_callback' => '__return_true',
			'args'                => [],
		],
	];
	return $endpoints;
} );




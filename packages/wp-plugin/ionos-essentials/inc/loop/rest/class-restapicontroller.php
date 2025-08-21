<?php

namespace ionos\essentials\loop\rest;
defined('ABSPATH') || exit();

use Ionos\Library\Config;

use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Controller for the RestApi.
 */
class RestApiController {

	// const API_NAMESPACE = 'ionos/v1';
	// const API_ENDPOINT  = '/loop';

	/**
	 * Gets the Rest API Endpoint URL.
	 *
	 * @return string
	 */
	public static function get_endpoint_url() {
		return get_home_url() . '/index.php?rest_route=/ionos/v1/loop';
	}

	/**
	 * Registers the routes.
	 */
	// public function register_routes() {
	// 	register_rest_route(
	// 		self::API_NAMESPACE,
	// 		self::API_ENDPOINT,
	// 		[
	// 			[
	// 				'methods'             => 'GET',
	// 				'callback'            => [ $this, 'get_loop' ],
	// 				'permission_callback' => [ $this, 'get_loop_permissions_check' ],
	// 			],
	// 			'schema' => [ $this, 'get_item_schema' ],
	// 		]
	// 	);
	// }

	/**
	 * Checks permissions for API Endpoint.
	 *
	 * @param WP_REST_Request $request Current request.
	 */
	public static function get_loop_permissions_check( $request ) {
		// if ( ! is_ssl() ) {
		// 	return new WP_Error( 'rest_forbidden_ssl', esc_html__( 'SSL required.' ), [ 'status' => 403 ] );
		// }

		// $remote_ip = $_SERVER['REMOTE_ADDR'];

		// // Checks if it is a valid IP address.
		// if ( filter_var( $remote_ip, FILTER_VALIDATE_IP ) === false ) {
		// 	return new WP_Error( 'rest_forbidden', esc_html__( 'Access forbidden.' ), [ 'status' => 403 ] );
		// }

		// // Checks if the request comes from IPv4.
		// if ( filter_var( $remote_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) !== false ) {
		// 	$ip_allowlist = Config::get( 'collector.allowlist.ipv4' );

		// 	if ( is_array( $ip_allowlist ) === false || $this->ipv4_in_allowlist( $remote_ip, $ip_allowlist ) === false ) {
		// 		return new WP_Error( 'rest_forbidden', esc_html__( 'Access forbidden.' ), [ 'status' => 403 ] );
		// 	}
		// }

		// // Checks if the request comes from IPv6.
		// if ( filter_var( $remote_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) !== false ) {
		// 	$ip_allowlist = Config::get( 'collector.allowlist.ipv6' );

		// 	if ( is_array( $ip_allowlist ) === false || $this->ipv6_in_allowlist( $remote_ip, $ip_allowlist ) === false ) {
		// 		return new WP_Error( 'rest_forbidden', esc_html__( 'Access forbidden.' ), [ 'status' => 403 ] );
		// 	}
		// }

		// // Checks if the Authorization header is set and public key is available.
		// $authorization_header = $request->get_header( 'X-Authorization' );
		// $public_key           = $this->get_public_key();
		// if ( $authorization_header === null || $public_key === null ) {
		// 	return new WP_Error( 'rest_forbidden', esc_html__( 'Unauthorized.' ), [ 'status' => 401 ] );
		// }

		// // Checks if the given token is valid and not outdated.
		// if ( $this->is_valid_authorization_header( $authorization_header, $public_key ) === false ) {
		// 	return new WP_Error( 'rest_forbidden', esc_html__( 'Unauthorized.' ), [ 'status' => 401 ] );
		// }

		return true;
	}

	/**
	 * Checks if an ip is in an ipv4 cidr allowlist.
	 *
	 * @param string $ipv4 IPv4 address.
	 * @param array  $allow_list CIDR alowlist.
	 *
	 * @return bool
	 */
	private function ipv4_in_allowlist( $ipv4, $allow_list ) {
		foreach ( $allow_list as $cidr ) {
			if ( $this->ipv4_in_cidr( $ipv4, $cidr ) === true ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks if an ip is in an ipv6 cidr allowlist.
	 *
	 * @param string $ipv6 IPv4 address.
	 * @param array  $allow_list CIDR alowlist.
	 *
	 * @return bool
	 */
	private function ipv6_in_allowlist( $ipv6, $allow_list ) {
		foreach ( $allow_list as $cidr ) {
			if ( $this->ipv6_in_cidr( $ipv6, $cidr ) === true ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks if IPv4 is in CIDR.
	 *
	 * @param string $ipv4 IPv4 address.
	 * @param string $cidr CIDR.
	 *
	 * @return bool
	 */
	private function ipv4_in_cidr( $ipv4, $cidr ) {
		list ( $subnet, $mask ) = explode( '/', $cidr );
		$subnet_addr            = ip2long( $subnet );
		$ip_addr                = ip2long( $ipv4 );
		$mask_addr              = -1 << ( 32 - $mask );
		return ( $subnet_addr & $mask_addr ) === ( $ip_addr & $mask_addr );
	}

	/**
	 * Checks if IPv6 is in CIDR.
	 *
	 * @since 1.0.0
	 *
	 * @param string $ipv6 IPv6 address.
	 * @param string $cidr CIDR.
	 *
	 * @return bool
	 */
	private function ipv6_in_cidr( $ipv6, $cidr ) {
		list ( $subnet_address, $subnet_mask ) = explode( '/', $cidr, 2 );

		if ( filter_var( $subnet_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) === false || $subnet_mask === null || $subnet_mask < 0 || $subnet_mask > 128 ) {
			return false;
		}

		$subnet  = inet_pton( $subnet_address );
		$address = inet_pton( $ipv6 );

		$binary_mask = str_repeat( 'f', $subnet_mask / 4 );
		switch ( $subnet_mask % 4 ) {
			case 0:
				break;
			case 1:
				$address .= '8';
				break;
			case 2:
				$address .= 'c';
				break;
			case 3:
				$address .= 'e';
				break;
		}
		$binary_mask = str_pad( $binary_mask, 32, '0' );
		$binary_mask = pack( 'H*', $binary_mask );

		return ( $address & $binary_mask ) === $subnet;
	}


	/**
	 * Checks if the given authorization header is valid.
	 *
	 * @param string $authorization_header Authorization Header value.
	 * @param string $public_key Public Key as String.
	 *
	 * @return bool
	 */
	private function is_valid_authorization_header( $authorization_header, $public_key ) {
		$auth_token = str_replace( 'Bearer ', '', $authorization_header );
		$token_data = explode( '.', $auth_token );

		if ( count( $token_data ) !== 2 ) {
			return false;
		}

		// The given token contains the data and signature seperated with a '.'.
		$data      = $token_data[0];
		$signature = hex2bin( $token_data[1] );

		if ( $signature === false ) {
			return false;
		}

		// Validate the given data using the signature and public key.
		$valid = openssl_verify( $data, $signature, $public_key, 'sha256WithRSAEncryption' );

		if ( $valid === 1 ) {
			$timestamp         = intval( base64_decode( $data ) ); // phpcs:ignore
			$current_timestamp = time();

			// Checks if the key is not older than 60 seconds.
			$time_difference = $current_timestamp - $timestamp;
			if ( $time_difference >= 0 && $time_difference < 60 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Fetches the public key for authorization.
	 *
	 * @return string|null The public key as string.
	 */
	private function get_public_key() {
		$cached_key = get_transient( 'ionos_loop_public_key' );
		if ( $cached_key !== false ) {
			return $cached_key;
		}

		// TODO: Build the url with the selected mode/env and tenant. Implement in the library first.
		$key_url = 'https://s3-de-central.profitbricks.com/web-hosting/ionos/live/config/loop/public-key.pem';
		$request = wp_remote_get( $key_url );
		if ( is_wp_error( $request ) ) {
			return null;
		}

		$public_key = wp_remote_retrieve_body( $request );
		if ( empty( $public_key ) ) {
			return null;
		}
		set_transient( 'ionos_loop_public_key', $public_key, 86400 );

		return $public_key;
	}

	/**
	 * Gathers the data and returns these.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public static function get_loop() {
		$core_data = [
			// 'generic' => GenericData::class,
			'user'    => count_users('memory'),
			// 'theme'   => ThemeData::class,
			// 'plugin'  => PluginData::class,
			// 'post'    => PostData::class,
			// 'comment' => CommentData::class,
			// 'surveys' => SurveyData::class,
		];

		// $providers = [];
		// $data      = [];

		// foreach ( $core_data as $key => $data_provider ) {
		// 	$providers[ $key ] = new $data_provider();
		// }

		// $option_keys = get_option( 'ionos_loop', [] );

		// if ( is_array( $option_keys ) ) {
		// 	foreach ( $option_keys as $option_key ) {
		// 		$providers[ $option_key ] = new CustomData( $option_key );
		// 	}
		// } else {
		// 	update_option( 'ionos_loop', [] );
		// }

		// /**
		//  * Collecting the data of all Data Providers.
		//  *
		//  * @var DataProvider[] $providers
		//  */
		// foreach ( $providers as $key => $provider ) {
		// 	$data[ $key ] = $provider->get_data();
		// }

		// return rest_ensure_response( $data );
	}
}

<?php

namespace ionos\essentials\loop;

defined('ABSPATH') || exit();

use ionos\essentials\loop\data\CustomDataStore;
use ionos\essentials\loop\rest\RestApiController;

/**
 * Main class of the plugin.
 */
class Plugin {

	/**
	 * Inits the plugin.
	 *
	 * Called on 'init'
	 */
	public static function init() {

		\add_action( 'ionos_loop_consent_given', [ __CLASS__, 'register_at_data_collector' ] );

		if ( empty( get_option( 'ionos_loop_consent', null ) ) ) {
			return;
		}

		\add_action( 'rest_api_init', [ __CLASS__, 'init_rest' ] );
		\add_action( 'ionos_loop_init_custom_store', [ __CLASS__, 'register_custom_data_store_action' ] );
	}

	/**
	 * Registers at the data collector.
	 */
	public static function register_at_data_collector() {
		update_option( 'ionos_loop_consent', true );

		if ( in_array( wp_get_environment_type(), [ 'local', 'development' ], true ) ) {
			return;
		}

		$http_args = [
			//phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
			'body'    => json_encode( [ 'url' => RestApiController::get_endpoint_url() ] ),
			'headers' => [
				'content-type' => 'application/json',
			],
		];

		$data_collector_url = "https:\/\/webapps-loop.hosting.ionos.com";//Config::get( 'collector.url' )
		if ( is_string( $data_collector_url ) ) {
			$response = wp_remote_post( $data_collector_url . '/api/register', $http_args );

			if ( is_wp_error( $response ) ) {
				echo esc_html( $response->get_error_message() );
			}
		}
	}

	/**
	 * Inits the Rest API.
	 *
	 * Called on 'rest_api_init'
	 */
	public static function init_rest() {
		// $survey_controller = new SurveyApi();
		// $survey_controller->register_rest_route();

		$controller = new RestApiController();
		$controller->register_routes();
	}

	/**
	 * Registers a custom data store via action.
	 *
	 * @param string $options_key The action key.
	 *
	 * @return void
	 */
	public static function register_custom_data_store_action( $options_key ) {
		static $instances = [];

		if ( ! isset( $instances[ $options_key ] ) || ! is_a( $instances[ $options_key ], CustomDataStore::class ) ) {
			$instances[ $options_key ] = new CustomDataStore( $options_key, true, true );
		}
	}

	/**
	 * Revokes the consent and deletes all data
	 */
	public static function revoke_consent() {
		foreach ( wp_load_alloptions() as $key => $value ) {
			if ( strpos( $key, 'ionos_loop_' ) !== false ) {
				delete_option( $key );
			}
		}
	}

}

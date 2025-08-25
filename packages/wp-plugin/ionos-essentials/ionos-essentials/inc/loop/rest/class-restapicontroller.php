<?php

namespace ionos\essentials\loop\rest;

defined('ABSPATH') || exit();

use WP_Error;
use WP_HTTP_Response;
use WP_REST_Response;

class RestApiController
{
  /**
   * Gathers the data and returns these.
   *
   * @return WP_Error|WP_HTTP_Response|WP_REST_Response
   */
  public static function get_loop()
  {
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

<?php

namespace ionos\essentials\loop;

use WP_REST_Request;
use WP_REST_Server;

defined('ABSPATH') || exit();

// option to keep last datacollector access timestamp
// also used to name the cron job for re registration of our endpoint
const IONOS_LOOP_DATACOLLECTOR_LAST_ACCESS      = 'ionos-essentials-loop-datacollector-last-access';
const IONOS_LOOP_REST_NAMESPACE                 = 'ionos/essentials/loop/v1';
const IONOS_LOOP_REST_ENDPOINT                  = '/loop-data';
const IONOS_LOOP_DATACOLLECTOR_REGISTRATION_URL = 'https://webapps-loop.hosting.ionos.com/api/register';

require_once __DIR__ . '/cron.php';
require_once __DIR__ . '/rest-permission-callback.php';
require_once __DIR__ . '/rest-callback.php';

/*
  registers our endpoint at the data collector
  @return bool true if registration was successful
 */
function _register_at_datacollector(): bool
{
  // skip registration for wp-env/local/dev environments
  if (in_array(\wp_get_environment_type(), ['local', 'development'], true)) {
    return true;
  }

  $response = \wp_remote_post(
    IONOS_LOOP_DATACOLLECTOR_REGISTRATION_URL,
    [
      'body'    => \wp_json_encode([
        'url' => \get_home_url() . '/index.php?rest_route=' . IONOS_LOOP_REST_NAMESPACE . IONOS_LOOP_REST_ENDPOINT,
      ]),
      'headers' => [
        'content-type' => 'application/json',
      ],
    ]
  );

  if (\is_wp_error($response)) {
    error_log(sprintf(
      'loop: Failed to register at loop datacollector(%s) : %s',
      join(', ', $response->get_error_codes()),
      join(PHP_EOL, $response->get_error_messages()),
    ));
  }

  return ! \is_wp_error($response);
}

\add_action('rest_api_init', function () {
  \register_rest_route(
    IONOS_LOOP_REST_NAMESPACE,
    IONOS_LOOP_REST_ENDPOINT,
    [
      'methods'             => WP_REST_Server::READABLE,
      'permission_callback' => __NAMESPACE__ . '\_rest_permissions_check',
      'callback'            => __NAMESPACE__ . '\_rest_loop_callback',
    ]
  );
});

// log loop endpoint errors to error log
\add_filter(
  hook_name : 'rest_request_after_callbacks',
  callback: function ($response, array $handler, WP_REST_Request $request) {
    $loop_endpoint_path = '/' . IONOS_LOOP_REST_NAMESPACE . IONOS_LOOP_REST_ENDPOINT;

    if (\is_wp_error($response) && $request->get_route() === $loop_endpoint_path) {
      error_log(sprintf(
        'loop: Failed to process loop request(%s) : %s',
        join(', ', $response->get_error_codes()),
        join(PHP_EOL, $response->get_error_messages()),
      ));
    }

    return $response;
  },
  accepted_args: 3
);

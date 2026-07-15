<?php

namespace ionos\ionos_core\loop;

use WP_REST_Request;
use WP_REST_Server;

defined('ABSPATH') || exit();

// option to keep last datacollector access timestamp
// also used to name the cron job for re registration of our endpoint
const IONOS_LOOP_DATACOLLECTOR_LAST_ACCESS      = 'ionos-essentials-loop-datacollector-last-access';
const IONOS_LOOP_REST_NAMESPACE                 = 'ionos/essentials/loop/v1';
const IONOS_LOOP_REST_ENDPOINT                  = '/loop-data';
const IONOS_LOOP_REST_CLICK_ENDPOINT            = '/click';
const IONOS_LOOP_REST_SSO_CLICK_ENDPOINT        = '/sso-click';
const IONOS_LOOP_DATACOLLECTOR_REGISTRATION_URL = 'https://webapps-loop.hosting.ionos.com/api/register';
const IONOS_LOOP_SSO_CLICK_OPTION               = 'ionos-loop-sso-click-timestamp';
const IONOS_LOOP_SSO_CLICK_WINDOW_SECONDS       = 60; // 1 minute

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
      'timeout' => 5,
      'body'    => \wp_json_encode([
        'url' => \get_home_url() . '/index.php?rest_route=/' . IONOS_LOOP_REST_NAMESPACE . IONOS_LOOP_REST_ENDPOINT,
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

function log_loop_event(string $name, array $payload = []): void
{
  $events = \get_option(IONOS_LOOP_EVENTS_OPTION, []);

  if (! is_array($events)) {
    $events = [];
  }

  $events[] = [
    'name'      => $name,
    'payload'   => $payload,
    'timestamp' => time(),  // current Unix timestamp (UTC)
  ];

  // Optional: limit stored events to avoid bloating options table
  $events = array_slice($events, -IONOS_LOOP_MAX_EVENTS);

  \update_option(IONOS_LOOP_EVENTS_OPTION, $events);
}

\add_action('wp_login', function ($user_login, $user) {
  $is_sso = ($_GET['action'] ?? '') === 'ionos_oauth_authenticate';

  $payload = [];

  if ($is_sso) {
    // Determine SSO source: Control Panel or wp-login page
    $sso_click_timestamp = \get_option(IONOS_LOOP_SSO_CLICK_OPTION, 0);
    $current_time        = time();
    $time_since_click    = $current_time - $sso_click_timestamp;

    // If click happened within the last 5 minutes, it's from wp-login
    $is_from_wp_login = $sso_click_timestamp > 0 && $time_since_click <= IONOS_LOOP_SSO_CLICK_WINDOW_SECONDS;

    $payload['type']   = 'sso';
    $payload['source'] = $is_from_wp_login ? 'wp-login' : 'control-panel';
  } else {
    // Password login - track admin vs non-admin
    $is_admin = false;

    if ($user instanceof \WP_User) {
      $is_admin = in_array('administrator', $user->roles ?? [], true) || $user->has_cap('manage_options');
    }

    $payload['type']     = 'password';
    $payload['is_admin'] = $is_admin;
  }

  log_loop_event('login', $payload);
}, 10, 2);

// revoke consent for legacy loop plugin
\add_action('init', function () {
  if (class_exists('\Ionos\Loop\Plugin')) {
    \add_option('ionos_loop_consent_LEGACY', \get_option('ionos_loop_consent', false), null, false);
    \Ionos\Loop\Plugin::revoke_consent();
  }
}, 90); // before legacy loop init at 99

add_filter('rest_endpoints', function (array $endpoints): array {
  $endpoints['/' . IONOS_LOOP_REST_NAMESPACE . IONOS_LOOP_REST_ENDPOINT] = [
    [
      'methods'             => WP_REST_Server::READABLE,
      'callback'            => __NAMESPACE__ . '\_rest_loop_callback',
      'permission_callback' => __NAMESPACE__ . '\_rest_permissions_check',
      'args'                => [],
    ],
  ];

  $endpoints['/' . IONOS_LOOP_REST_NAMESPACE . IONOS_LOOP_REST_CLICK_ENDPOINT] = [
    [
      'methods'             => 'POST',
      'callback'            => __NAMESPACE__ . '\_rest_loop_click_callback',
      'permission_callback' => fn () => 0 !== \get_current_user_id(),
      'args'                => [],
    ],
  ];

  $endpoints['/' . IONOS_LOOP_REST_NAMESPACE . IONOS_LOOP_REST_SSO_CLICK_ENDPOINT] = [
    [
      'methods'             => 'POST',
      'callback'            => __NAMESPACE__ . '\_rest_sso_click_callback',
      'permission_callback' => __NAMESPACE__ . '\_rest_sso_click_permissions_check',
      'args'                => [],
    ],
  ];

  return $endpoints;
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

function get_ssl_type(): string
{
  $host = \parse_url(\home_url(), PHP_URL_HOST);

  if (! $host) {
    return 'no host';
  }

  $context = stream_context_create([
    'ssl' => [
      'capture_peer_cert' => true,
    ],
  ]);

  $client = @stream_socket_client("ssl://{$host}:443", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context);

  if (! $client) {
    return 'no client';
  }

  $params = stream_context_get_params($client);
  fclose($client);

  $peer_certificate = $params['options']['ssl']['peer_certificate'] ?? null;
  $cert             = $peer_certificate ? openssl_x509_parse($peer_certificate) : false;

  if (! is_array($cert)) {
    return 'no cert';
  }

  // EV certificates carry OID 2.23.140.1.1 in their Certificate Policies extension
  $policies = $cert['extensions']['certificatePolicies'] ?? '';
  if (str_contains($policies, '2.23.140.1.1')) {
    return 'EV';
  }

  // OV certificates include the Organization field in the subject
  if (! empty($cert['subject']['O'])) {
    return 'OV';
  }

  return 'DV';
}

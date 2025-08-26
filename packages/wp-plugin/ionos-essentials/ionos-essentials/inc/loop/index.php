<?php

namespace ionos\essentials\loop;

/*
  @TODO: describe how loop the feature works
*/

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

defined('ABSPATH') || exit();

// taken from https://web-hosting.s3-eu-central-1.ionoscloud.com/ionos/live/config/loop/default/config.json
const IONOS_LOOP_ALLOW_LIST_IP4 = [
  "10.67.48.0\/22",
  "10.67.84.0\/22",
  "100.68.0.0\/16",
  "74.208.114.128\/26",
  "74.208.114.192\/26",
  "77.68.65.64\/26",
  "82.165.226.0\/26",
  "82.165.226.64\/26",
  "88.208.254.128\/26",
  "82.223.158.0\/26",
  "213.171.202.197\/27",
];

const IONOS_LOOP_ALLOW_LIST_IP6 = [
  "2607:f1c0:5c0:53::\/64",
  "2607:f1c0:5c1:53::\/64",
  "2001:8d8:5c0:453::\/64",
  "2001:8d8:5c1:453::\/64",
  "2001:ba0:5c0::\/64",
  "2a00:da00:5c0::\/64",
  "2a00:da00:5c0:3::\/64",
  "2a00:da00:5c0:4::\/64",
];

const IONOS_LOOP_DATACOLLECTOR_PUBLICKEY_TRANSIENT = 'ionos-essentials-loop-datacollector-public-key';
// option to keep last datacollector access timestamp
// also used to name the cron job for re registration of our endpoint
const IONOS_LOOP_DATACOLLECTOR_LAST_ACCESS = 'ionos-essentials-loop-datacollector-last-access';
const IONOS_LOOP_REST_NAMESPACE = 'ionos/essentials/loop/v1';
const IONOS_LOOP_REST_ENDPOINT = '/loop-data';
const IONOS_LOOP_DATACOLLECTOR_REGISTRATION_URL = 'https://webapps-loop.hosting.ionos.com/api/register';
const IONOS_LOOP_DATACOLLECTOR_PUBLIC_KEY_URL = 'https://s3-de-central.profitbricks.com/web-hosting/ionos/live/config/loop/public-key.pem';

/*
  registers our endpoint at the data collector

  @return bool true if registration was successful
*/
function _register_at_datacollector() : bool
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
  ]);

  if (! \is_wp_error($response)) {
    // @TODO : what should we do if registration failed ?
    // try again after a interval or what
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
      'callback'            => __NAMESPACE__ . '\_rest_loop_data'
    ]
  );
});

function _rest_permissions_check(WP_REST_Request $request) : bool|WP_Error {
  // skip permission check for wp-env/local/dev environments
  if (in_array(\wp_get_environment_type(), ['local', 'development'], true)) {
    return true;
  }

  if ( ! is_ssl() ) {
  	return new \WP_Error( 'rest_forbidden_ssl', 'SSL required.', [ 'status' => 403 ] );
  }

  $remote_ip = $_SERVER['REMOTE_ADDR'];

  // Checks if it is a valid IP address.
  if ( !filter_var( $remote_ip, FILTER_VALIDATE_IP ) ) {
  	return new \WP_Error( 'rest_forbidden', 'Access forbidden.', [ 'status' => 403 ] );
  }

  // Checks if the request comes from IPv4.
  if ( !filter_var( $remote_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 )) {
  	if ( !_ipv4_in_allowlist( $remote_ip, IONOS_LOOP_ALLOW_LIST_IP4)) {
  		return new \WP_Error( 'rest_forbidden', 'Access forbidden.', [ 'status' => 403 ] );
  	}
  }

  // Checks if the request comes from IPv6.
  if ( !filter_var( $remote_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 )) {
  	if ( !_ipv6_in_allowlist( $remote_ip, IONOS_LOOP_ALLOW_LIST_IP6 ) ) {
  		return new \WP_Error( 'rest_forbidden', 'Access forbidden.', [ 'status' => 403 ] );
  	}
  }

  // Checks if the Authorization header is set and public key is available.
  $authorization_header = $request->get_header( 'X-Authorization' );
  $public_key           = _get_public_key();
  if ( $authorization_header === null || \is_wp_error($public_key)) {
  	return new \WP_Error( 'rest_forbidden', 'Unauthorized.', [ 'status' => 401 ] );
  }

  // Checks if the given token is valid and not outdated.
  if ( !_is_valid_authorization_header( $authorization_header, $public_key ) ) {
  	return new \WP_Error( 'rest_forbidden', 'Unauthorized.', [ 'status' => 401 ] );
  }

  return true;
}

function _ipv4_in_allowlist(string $ipv4, array $allow_list) : bool
{
  foreach ($allow_list as $cidr) {
    if (_ipv4_in_cidr($ipv4, $cidr) === true) {
      return true;
    }
  }
  return false;
}

function _ipv6_in_allowlist(string $ipv6, array $allow_list) : bool
{
  foreach ($allow_list as $cidr) {
    if (_ipv6_in_cidr($ipv6, $cidr) === true) {
      return true;
    }
  }
  return false;
}

function _ipv4_in_cidr(string $ipv4, string $cidr) : bool
{
  list($subnet, $mask)    = explode('/', $cidr);
  $subnet_addr            = ip2long($subnet);
  $ip_addr                = ip2long($ipv4);
  $mask_addr              = -1 << (32 - $mask);
  return ($subnet_addr & $mask_addr) === ($ip_addr & $mask_addr);
}

function _ipv6_in_cidr(string $ipv6, string $cidr) : bool
{
  list($subnet_address, $subnet_mask) = explode('/', $cidr, 2);

  if (filter_var(
    $subnet_address,
    FILTER_VALIDATE_IP,
    FILTER_FLAG_IPV6
  ) === false || $subnet_mask === null || $subnet_mask < 0 || $subnet_mask > 128) {
    return false;
  }

  $subnet  = inet_pton($subnet_address);
  $address = inet_pton($ipv6);

  $binary_mask = str_repeat('f', $subnet_mask / 4);
  $binary_mask = str_pad($binary_mask, 32, '0');
  $binary_mask = pack('H*', $binary_mask);

  $address = match($subnet_mask % 4) {
    0 => $address,
    1 => $address . '8',
    2 => $address . 'c',
    3 => $address . 'e',
  };

  return ($address & $binary_mask) === $subnet;
}

function _is_valid_authorization_header(string $authorization_header, string $public_key) : bool
{
  $auth_token = str_replace('Bearer ', '', $authorization_header);
  $token_data = explode('.', $auth_token);

  if (count($token_data) !== 2) {
    return false;
  }

  // The given token contains the data and signature seperated with a '.'.
  $data      = $token_data[0];
  $signature = hex2bin($token_data[1]);

  if ($signature === false) {
    return false;
  }

  // Validate the given data using the signature and public key.
  $valid = openssl_verify($data, $signature, $public_key, 'sha256WithRSAEncryption');

  if ($valid === 1) {
    $timestamp         = intval(base64_decode($data)); // phpcs:ignore
    $current_timestamp = time();

    // Checks if the key is not older than 60 seconds.
    $time_difference = $current_timestamp - $timestamp;
    if ($time_difference >= 0 && $time_difference < 60) {
      return true;
    }
  }

  return false;
}

function _get_public_key() : string|WP_Error
{
  $cached_key = \get_transient(IONOS_LOOP_DATACOLLECTOR_PUBLICKEY_TRANSIENT);
  if ($cached_key !== false) {
    return $cached_key;
  }

  $request = \wp_remote_get(IONOS_LOOP_DATACOLLECTOR_PUBLIC_KEY_URL);
  if (\is_wp_error($request)) {
    return $request;
  }

  $public_key = \wp_remote_retrieve_body($request);
  if (empty($public_key)) {
    return new \WP_Error('no_public_key', 'empty body retrieved from ' . IONOS_LOOP_DATACOLLECTOR_PUBLIC_KEY_URL);
  }
  \set_transient(IONOS_LOOP_DATACOLLECTOR_PUBLICKEY_TRANSIENT, $public_key, 86400);

  return $public_key;
}

function _rest_loop_data(WP_REST_Request $request) : \WP_REST_Response {
  \add_option(IONOS_LOOP_DATACOLLECTOR_LAST_ACCESS, time());

  $core_data = [
    'user' => count_users('memory'),
    'theme'   => _get_themes_data(),
    'plugin'  => _get_plugins_data(),
    'post_and_pages'    => _get_posts_and_pages_data(),
    'comment' => _get_comments_data(),
  ];
  return \rest_ensure_response($core_data);
}

function _get_themes_data() {
  // Get all installed themes
  $all_themes = wp_get_themes();
  $total_themes = count($all_themes);

  // Get the current active theme
  $current_theme = wp_get_theme();

  $parent_theme_slug = $current_theme->parent() ? $current_theme->parent()->get_stylesheet() : null;


  $auto_update_themes = get_site_option('auto_update_themes', []);
  $current_theme_slug = $current_theme->get_stylesheet();
  $auto_update = in_array($current_theme_slug, $auto_update_themes, true);

  return [
      'total' => $total_themes,
      'active_theme' => [
          'id'                => $current_theme_slug,
          'version'           => $current_theme->get('Version'),
          'active'            => true,
          'parent_theme_slug' => $parent_theme_slug,
          'auto_update'       => $auto_update,
          'requires_php'      => $current_theme->get('RequiresPHP') ?: null,
          'requires_wp'       => $current_theme->get('RequiresWP') ?: null,
      ],
  ];
}


function _get_plugins_data() : array
{
  if ( ! function_exists('get_plugins') ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $all_plugins = get_plugins();
    $total_plugins = count($all_plugins);

    $active_plugins = get_option('active_plugins', []);
    $total_active_plugins = count($active_plugins);

    return [
        'total_installed' => $total_plugins,
        'total_active'    => $total_active_plugins,
    ];
}

function _get_posts_and_pages_data() : array
{
  $data = [];

  $post_counts = wp_count_posts('post');
  $page_counts = wp_count_posts('page');

  $data['published_posts'] = isset($post_counts->publish) ? (int) $post_counts->publish : 0;
  $data['published_pages'] = isset($page_counts->publish) ? (int) $page_counts->publish : 0;

  return $data;
}

function _get_comments_data(): array
{
    $comments_data = [];

    $comments_data['default_comment_status'] = get_option('default_comment_status');

    $comment_counts = wp_count_comments();

    if ($comment_counts && is_object($comment_counts)) {
        $comments_data['total_comments'] = array_sum((array) $comment_counts);
    } else {
        $comments_data['total_comments'] = 0;
    }

    return $comments_data;
}

require_once __DIR__ . '/cron.php';

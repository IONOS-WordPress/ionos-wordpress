<?php

namespace ionos\essentials\dashboard\blocks\adserver;

defined('ABSPATH') || exit();

use const ionos\essentials\PLUGIN_FILE;

function render(): void
{
  $params = [
    'token'       => \get_transient('ionos_adserver_token') ?: 'adserver_default_token',
    'zoneid'      => \wp_get_environment_type() !== 'local' ? 'wp_admin_overview_card_left' : 'developers_docs_example',
    'visitorData' => [
      'beyondseo'        => \is_plugin_active('ionos-essentials/ionos-essentials.php') ? true : false,
      'market'           => \get_option('ionos_market', 'not set'),
      'privacy_complete' => get_privacy_complete(),
      'ssl_type'         => get_ssl_type(),
    ],
    'c' => [
      'language'  => \get_bloginfo('language') ? str_replace('-', '_', \get_bloginfo('language')) : 'de_DE',
    ],
    'nonce'       => \wp_create_nonce('wp_rest'),
    'proxyUrl'    => \rest_url('ionos/essentials/adzone/v1/proxy'),
  ];

  $url   = \plugins_url(sprintf(
    '/ionos-essentials/inc/dashboard/blocks/adserver/view.html?params=%s',
    urlencode(json_encode($params))
  ), PLUGIN_FILE);

  printf(
    '<iframe src="%s" id="adzone" style="display: none; height: 0px; width: 100%%;margin-bottom: 32px;border-radius:var(--default-border-radius, 16px);" ></iframe>',
    esc_url($url)
  );
}

function get_privacy_complete(): bool
{
  if ($post = get_post(\get_option('wp_page_for_privacy_policy'))) {
    if (! empty(trim($post->post_content))) {
      return true;
    }
  }
  return false;
}

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

  $client = @stream_socket_client("ssl://{$host}:443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);

  if (! $client) {
    return 'no client';
  }

  $params = stream_context_get_params($client);
  $cert   = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
  fclose($client);

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

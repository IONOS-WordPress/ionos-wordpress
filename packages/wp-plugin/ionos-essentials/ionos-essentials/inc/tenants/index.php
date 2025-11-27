<?php

namespace ionos\essentials\tenant;

defined('ABSPATH') || exit();

use ionos\essentials\Tenant;

/**
 * Get the data for the current tenant.
 *
 * @return array|null
 */
function get_tenant_config()
{
  static $data = null;

  if (null !== $data) {
    return $data;
  }

  $tenant      = Tenant::get_slug();
  $config_file = __DIR__ . '/' . $tenant . '.php';

  if (! $tenant || ! file_exists($config_file)) {
    return null;
  }

  require $config_file;

  $market        = strtolower(\get_option($tenant . '_market', 'de'));
  $webmail_links = '';
  if ('ionos' === $tenant) {
    $webmail_links = $webmailloginlinks[$market] ?? $webmailloginlinks['de'];
  } elseif ('homepl' === $tenant) {
    $webmail_links = $webmailloginlinks['pl'];
  }

  $nba_links    = $nba_links    ?? '';
  $banner_links = $banner_links ?? '';

  $domain   = $market_domains[$market] ?? reset($market_domains);

  $data = [
    'links'        => $links,
    'domain'       => $domain,
    'market'       => $market,
    'tenant'       => $tenant,
    'nba_links'    => $nba_links,
    'webmail'      => $webmail_links,
    'banner_links' => $banner_links,
  ];

  return $data;
}

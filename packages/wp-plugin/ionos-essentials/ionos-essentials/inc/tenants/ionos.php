<?php

namespace ionos\essentials\tenant;

defined('ABSPATH')                          || exit();

if (! defined('SFS_IS_SET')) {
  define('SFS_IS_SET', array_key_exists('SFS', $_SERVER));
}
$is_sfs = SFS_IS_SET || get_option('sfs_website_id');

$links = $is_sfs ? [
  [
    'url'    => 'log-analysis',
    'anchor' => __('Log Analysis', 'ionos-essentials'),
  ],
  [
    'url'    => 'manage-cache',
    'anchor' => __('Manage Cache', 'ionos-essentials'),
  ],
] : [
  [
    'url'    => 'account',
    'anchor' => __('My Account', 'ionos-essentials'),
  ],
  [
    'url'    => 'websites',
    'anchor' => __('Project Overview', 'ionos-essentials'),
  ],
  [
    'url'    => 'domains',
    'anchor' => __('Domains and SSL management', 'ionos-essentials'),
  ],
  [
    'url'    => 'product-overview',
    'anchor' => __('My Products', 'ionos-essentials'),
  ],
  [
    'url'    => 'add-product',
    'anchor' => __('Add more products', 'ionos-essentials'),
  ],
];

// Trailing slash
// The first market is the default market
$market_domains = $is_sfs ? [
  'de'      => 'https://' . (get_option('sfs_panel_hostname') ?: 'stretch.ionos.org') . '/websites/' . get_option(
    'sfs_website_id'
  ) . '/',
] : [
  'de'      => 'https://mein.ionos.de/',
  'uk'      => 'https://my.ionos.co.uk/',
  'gb'      => 'https://my.ionos.co.uk/',
  'fr'      => 'https://my.ionos.fr/',
  'us'      => 'https://my.ionos.com/',
  'es'      => 'https://my.ionos.es/',
  'it'      => 'https://my.ionos.it/',
  'mx'      => 'https://my.ionos.mx/',
];

$webmailloginlinks = $is_sfs ? [] : [
  'de' => 'https://id.ionos.de/',
  'uk' => 'https://id.ionos.co.uk/',
  'gb' => 'https://id.ionos.co.uk/',
  'fr' => 'https://id.ionos.fr/',
  'us' => 'https://id.ionos.com/',
  'es' => 'https://id.ionos.es/',
  'it' => 'https://id.ionos.it/',
  'mx' => 'https://id.ionos.mx/',
];

$nba_links = [
  'connectdomain' => $is_sfs
      ? 'domain-configuration'
      : 'domains',
  'connectmail'   => 'email-portfolio?',
];

$banner_links = [
  'managehosting' => $is_sfs ? '' : 'websites',
];

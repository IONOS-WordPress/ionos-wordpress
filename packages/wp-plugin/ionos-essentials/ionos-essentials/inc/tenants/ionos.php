<?php

namespace ionos\essentials\tenant;

defined('ABSPATH') || exit();

$links = [
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
$market_domains = [
  'de'      => 'https://mein.ionos.de/',
  'uk'      => 'https://my.ionos.co.uk/',
  'gb'      => 'https://my.ionos.co.uk/',
  'fr'      => 'https://my.ionos.fr/',
  'us'      => 'https://my.ionos.com/',
  'es'      => 'https://my.ionos.es/',
  'it'      => 'https://my.ionos.it/',
  'mx'      => 'https://my.ionos.mx/',
  'stretch' => 'https://' . ( get_option('ionos_sfs_panel_hostname') ?: 'stretch.ionos.org' ) . '/websites/',
];

$webmailloginlinks = [
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
    'connectdomain' => get_option('ionos_sfs_website_id')
        ? get_option('ionos_sfs_website_id') . '/domain-configuration'
        : 'domains',
    'connectmail'   => 'email-portfolio?',
];

$banner_links = [
  'managehosting' => 'websites',
];

$sfs_links = [
  [
    'url'    => 'log-analysis',
    'anchor' => __('Log Analysis', 'ionos-essentials'),
  ],
  [
    'url'    => 'manage-cache',
    'anchor' => __('Manage Cache', 'ionos-essentials'),
  ],
];

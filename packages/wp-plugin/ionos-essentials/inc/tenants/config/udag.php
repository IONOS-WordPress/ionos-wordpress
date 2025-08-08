<?php

namespace ionos\essentials\tenants\config;

defined('ABSPATH') || exit();

$links = [
  [
    'url'    => 'a/user/data',
    'anchor' => __('My data', 'ionos-essentials'),
  ],
  [
    'url'    => 'a/wordpress-hosting',
    'anchor' => __('Manage WordPress', 'ionos-essentials'),
  ],
  [
    'url'    => 'a/domain-list',
    'anchor' => __('Manage Domains', 'ionos-essentials'),
  ],
  [
    'url'    => 'a/mail-domains',
    'anchor' => __('Manage e-mail', 'ionos-essentials'),
  ],
  [
    'url'    => 'a/webspace',
    'anchor' => __('Webspace', 'ionos-essentials'),
  ],
  [
    'url'    => 'a/ranking-coach',
    'anchor' => __('RankingCoach', 'ionos-essentials'),
  ],
  [
    'url'    => 'a/ssl',
    'anchor' => __('SSL', 'ionos-essentials'),
  ],
  [
    'url'    => 'a/marken/markenschutz',
    'anchor' => __('Trademark protection', 'ionos-essentials'),
  ],
];

// Trailing slash
// The first market is the default market
$market_domains = [
  'de' => 'https://www.united-domains.de/portfolio/',
];

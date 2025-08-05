<?php

namespace ionos\essentials\dashboard\blocks\deep_links;

defined('ABSPATH') || exit();

$links = [
  [
    'url'    => 'a/user/data',
    'anchor' => __('Meine Daten', 'ionos-essentials'),
  ],
  [
    'url'    => 'a/wordpress-hosting',
    'anchor' => __('WordPress verwalten ', 'ionos-essentials'),
  ],
  [
    'url'    => 'a/domain-list',
    'anchor' => __('Domains verwalten', 'ionos-essentials'),
  ],
  [
    'url'    => 'a/mail-domains',
    'anchor' => __('E-Mail verwalten', 'ionos-essentials'),
  ],
  [
    'url'    => 'a/webspace',
    'anchor' => __('Webspace ', 'ionos-essentials'),
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
    'anchor' => __('Markenschutz', 'ionos-essentials'),
  ],
];

// Trailing slash
// The first market is the default market
$market_domains = [
  'de' => 'https://www.united-domains.de/portfolio/',
];

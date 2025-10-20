<?php

namespace ionos\essentials\tenants\config;

defined('ABSPATH') || exit();

$links = [
  [
    'url'    => '',
    'anchor' => __('My Products', 'ionos-essentials'),
  ],
  [
    'url'    => '',
    'anchor' => __('My Account', 'ionos-essentials'),
  ],
  [
    'url'    => '',
    'anchor' => __('Project Overview', 'ionos-essentials'),
  ],
  [
    'url'    => '',
    'anchor' => __('Domains and SSL management', 'ionos-essentials'),
  ],
  [
    'url'    => '',
    'anchor' => __('Add more products', 'ionos-essentials'),
  ]
];

// Trailing slash
// The first market is the default market
$market_domains = [
  'pl' => 'https://panel.home.pl/',
];

$nba_link = [
  'connectdomain' => '',
  'connectmail'   => '',
];

$webmailloginlinks = [
  'pl' => 'https://login.poczta.home.pl/'
];
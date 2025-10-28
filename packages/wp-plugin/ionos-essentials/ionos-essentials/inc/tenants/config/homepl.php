<?php

namespace ionos\essentials\tenants\config;

defined('ABSPATH') || exit();

$links = [
  [
    'url'    => '',
    'anchor' => __('Manage Hosting', 'ionos-essentials'),
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
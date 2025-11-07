<?php

namespace ionos\essentials\tenant;

defined('ABSPATH') || exit();

$links = [
  [
    'url'    => 'catalogue',
    'anchor' => __('My offers', 'ionos-essentials'),
  ],
  [
    'url'    => 'clients/data',
    'anchor' => __('My data', 'ionos-essentials'),
  ],
  [
    'url'    => '',
    'anchor' => __('My products', 'ionos-essentials'),
  ],
  [
    'url'    => '/invoices/list',
    'anchor' => __('My invoices', 'ionos-essentials'),
  ],
  [
    'url'    => 'container',
    'anchor' => __('Control Panel', 'ionos-essentials'),
  ],
];

// Trailing slash
// The first market is the default market
$market_domains = [
  'es' => 'https://secure.arsys.es/',
];

$nba_link = [
  'connectdomain' => 'catalogue?data={"sections":[{"name":"domains","open":true}]}',
  'connectmail'   => 'catalogue?data={"sections":[{"name":"mail","open":true}]}',
];

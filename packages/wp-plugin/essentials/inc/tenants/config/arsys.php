<?php

namespace ionos_wordpress\essentials\dashboard\blocks\deep_links;

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

// Trainling slash
// The first market is the default market
$market_domains = [
  'es' => 'https://secure.arsys.es/',
];

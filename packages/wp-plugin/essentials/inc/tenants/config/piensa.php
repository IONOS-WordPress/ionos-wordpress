<?php

namespace ionos_wordpress\essentials\dashboard\blocks\deep_links;

$links = [
  [
    'url'    => 'catalogue',
    'anchor' => __('My offers', 'ionos-essentials'),
  ],
  [
    'url'    => 'clients/data',
    'anchor' => __('Data of customer', 'ionos-essentials'),
  ],
  [
    'url'    => '',
    'anchor' => __('Services', 'ionos-essentials'),
  ],
  [
    'url'    => '/invoices/list',
    'anchor' => __('Invoices', 'ionos-essentials'),
  ],
  [
    'url'    => 'container',
    'anchor' => __('Control Panel', 'ionos-essentials'),
  ],
];

// Trainling slash
// The first market is the default market
$market_domains = [
  'es' => 'https://secure.piensasolutions.com/',
];

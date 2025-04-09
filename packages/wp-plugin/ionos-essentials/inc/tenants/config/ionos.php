<?php

namespace ionos\essentials\dashboard\blocks\deep_links;

$links = [
  [
    'url'    => 'product-overview',
    'anchor' => __('My Products', 'ionos-essentials'),
  ],
  [
    'url'    => 'account-security',
    'anchor' => __('Login & Account Security', 'ionos-essentials'),
  ],
  [
    'url'    => 'website',
    'anchor' => __('Project Overview', 'ionos-essentials'),
  ],
  [
    'url'    => 'domains',
    'anchor' => __('Domains and SSL management', 'ionos-essentials'),
  ],
  [
    'url'    => 'add-product',
    'anchor' => __('Add more products', 'ionos-essentials'),
  ],
];

// Trainling slash
// The first market is the default market
$market_domains = [
  'de' => 'https://mein.ionos.de/',
  'uk' => 'https://my.ionos.co.uk/',
  'gb' => 'https://my.ionos.co.uk/',
  'fr' => 'https://my.ionos.fr/',
  'us' => 'https://my.ionos.com/',
  'es' => 'https://my.ionos.es/',
  'ca' => 'https://my.ionos.ca/',
  'it' => 'https://my.ionos.it/',
  'mx' => 'https://my.ionos.mx/',
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
  'connectdomain' => 'domains',
  'connectmail'   => 'email-portfolio?',
];

$banner_links = [
  'managehosting' => 'websites',
];

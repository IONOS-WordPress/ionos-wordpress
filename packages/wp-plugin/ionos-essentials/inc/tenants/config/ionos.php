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
  'de' =>
  [ 'url' => 'https://id.ionos.de/',
    'anchor' => __('Webmail Login', 'ionos-essentials'),
  ],
  'uk' =>
  [ 'url' => 'https://id.ionos.co.uk/',
    'anchor' => __('Webmail Login', 'ionos-essentials'),
  ],
  'gb' =>
  [ 'url' => 'https://id.ionos.co.uk/',
    'anchor' => __('Webmail Login', 'ionos-essentials'),
  ],
  'fr' =>
  [ 'url' => 'https://id.ionos.fr/',
    'anchor' => __('Connexion à la messagerie Web', 'ionos-essentials'),
  ],
  'us' =>
  [ 'url' => 'https://id.ionos.com/',
    'anchor' => __('Webmail Login', 'ionos-essentials'),
  ],
  'es' =>
  [ 'url' => 'https://id.ionos.es/',
    'anchor' => __('Acceso al webmail', 'ionos-essentials'),
  ],
  'it' =>
  [ 'url' => 'https://id.ionos.it/',
    'anchor' => __('Accesso Webmail', 'ionos-essentials'),
  ],
  'mx' =>
  [ 'url' => 'https://id.ionos.mx/',
    'anchor' => __('Acceso al webmail', 'ionos-essentials'),
  ],
];

$nba_links = [
  'connectdomain' => 'domains',
  'connectmail'   => 'email-portfolio?',
];

$banner_links = [
  'managehosting' => 'websites',
];

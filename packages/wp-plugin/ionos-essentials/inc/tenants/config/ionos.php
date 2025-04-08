<?php

namespace ionos\essentials\dashboard\blocks\deep_links;

$links = [
  [
    'url'    => 'subscriptions?filter.contract=any&filter.type=any',
    'anchor' => __('Contracts & Subscriptions', 'ionos-essentials'),
  ],
  [
    'url'    => 'account-security',
    'anchor' => __('Login & Account Security', 'ionos-essentials'),
  ],
  [
    'url'    => 'invoices',
    'anchor' => __('Invoices & Payment Details', 'ionos-essentials'),
  ],
  [
    'url'    => 'privacy-overview',
    'anchor' => __('Data Protection & Privacy Notice', 'ionos-essentials'),
  ],
  [
    'url'    => 'address',
    'anchor' => __('Contact Details', 'ionos-essentials'),
  ],
  [
    'url'    => 'account/edit-customerprofile',
    'anchor' => __('Profile Details', 'ionos-essentials'),
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

$nba_link = [
  'connectdomain' => 'domains',
  'connectmail'   => 'email-portfolio?',
];

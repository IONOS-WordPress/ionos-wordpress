<?php

namespace ionos_wordpress\essentials\dashboard\blocks\deep_links;

const links = [
  [
    'url' => 'subscriptions?filter.contract=any&filter.type=any',
    'anchor' => __('Contracts & Subscriptions', 'ionos-essentials'),
  ],
  [
    'url' => 'account-security',
    'anchor' => __('Login & Account security', 'ionos-essentials'),
  ],
  [
    'url' => 'invoices',
    'anchor' => __('Invoices & Payment Details', 'ionos-essentials'),
  ],
  [
    'url' => 'privacy-overview',
    'anchor' => __('Data Protection & Privacy Notice', 'ionos-essentials'),
  ],
  [
    'url' => 'address',
    'anchor' => __('Contact Details', 'ionos-essentials'),
  ],
  [
    'url' => 'account/edit-customerprofile',
    'anchor' => __('Profile Details', 'ionos-essentials'),
  ],
];

// Trainling slash
// The first market is the default market
const market_domains = [
  'de' => 'https://my.ionos.com/',
  'uk' => 'my.ionos.co.uk',
  'gb' => 'my.ionos.co.uk',
  'ft' => 'my.ionos.fr',
  'us' => 'my.ionos.com',
  'es' => 'my.ionos.es',
  'ca' => 'my.ionos.ca',
  'it' => 'my.ionos.it',
  'mx' => 'my.ionos.mx',
];

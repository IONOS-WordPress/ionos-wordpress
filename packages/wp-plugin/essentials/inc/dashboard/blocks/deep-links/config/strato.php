<?php

namespace ionos_wordpress\essentials\dashboard\blocks\deep_links;

$links = [
  [
    'url'    => 'apps/CustomerService?dlink=kds_CustomerEntryPage',
    'anchor' => __('Package overview', 'ionos-essentials'),
  ],
  [
    'url'    => 'apps/CustomerService?dlink=OnlineInvoice',
    'anchor' => __('Your invoices', 'ionos-essentials'),
  ],
  [
    'url'    => 'apps/CustomerService?dlink=kds_Vertragsbetreuung_2',
    'anchor' => __('Your contract', 'ionos-essentials'),
  ],
  [
    'url'    => 'apps/CustomerService?dlink=Offers_EntryPage',
    'anchor' => __('Current offers', 'ionos-essentials'),
  ],
];

$market_domains = [
  'de' => 'https://strato.de/',
  'es' => 'https://strato.es/',
  'se' => 'https://strato.se/',
  'nl' => 'https://strato.nl/',
];

<?php

namespace ionos\essentials\tenants\config;

defined('ABSPATH') || exit();

$links = [
  [
    'url'    => '',
    'anchor' => __('Control Panel', 'ionos-essentials'),
  ],
  [
    'url'    => 'Profile',
    'anchor' => __('My User', 'ionos-essentials'),
  ],
  [
    'url'    => 'Hosting/Websites/WordPress',
    'anchor' => __('WordPress Websites', 'ionos-essentials'),
  ],
];

// Trailing slash
// The first market is the default market
$market_domains = [
  'en' => 'https://admin.fasthosts.co.uk/',
];

$nba_link = [
  'connectdomain' => '',
  'connectmail'   => 'HostingPackages/[[PACKAGE_INSTANCE_ID]]/Email/Service/[[MAIL_SERVICE_ID]]',
];

<?php

namespace ionos\ionos_core\marketplace\extendify;

defined('ABSPATH') || exit();

const EXTENDIFY_URL_TEMPLATE = 'https://s3-de-central.profitbricks.com/web-hosting/extendify/%s.zip';
const EXTENDIFY_BASE = '01-ext-';

const EXTENDIFY_TENANT_CODES = [
  'arsys' => ['ars', '792ni2', '3y0s1r'],
  'fasthosts' => ['fst', '0j912l', '8a6h8s'],
  'homepl' => ['hpl', '4n38mn', '8m4p1l'],
  'ionos' => ['ion', '8dhas7', '2h8n7s'],
  'piensa' => ['pie', '1k10e2', '2z0n1s'],
  'strato' => ['str', '10jh8a', '1a4d6o'],
  'udag' => ['udg', '00z90a', '5y4a8g'],
  'world4you' => ['w4y', '0asjd8', '9b0y1u'],
];

function get_extendify_license(string $flavor = 'onboarding'): string {
  if (\defined('EXTENDIFY_PARTNER_ID')) {
    return constant('EXTENDIFY_PARTNER_ID');
  }

  $tenant = strtolower(\get_option('ionos_group_brand', 'ionos'));
  $flavor = strtolower($flavor);

  if ($flavor !== 'full' && $flavor !== 'onboarding') {
    $flavor = 'onboarding';
  }

  $codes = EXTENDIFY_TENANT_CODES[$tenant] ?? EXTENDIFY_TENANT_CODES['ionos'];
  $suffix = ($flavor === 'full') ? $codes[1] : $codes[2];

  return EXTENDIFY_BASE . $codes[0] . $suffix;
}

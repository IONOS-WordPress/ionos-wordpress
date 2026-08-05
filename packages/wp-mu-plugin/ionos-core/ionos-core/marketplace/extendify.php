<?php

namespace ionos\ionos_core\marketplace\extendify;

defined('ABSPATH') || exit();

const SITE_ASSISTANT_URL_TEMPLATE = 'https://s3-eu-central-1.ionoscloud.com/web-hosting/extendify/%s.zip';
const SITE_ASSISTANT_OPTION_KEY   = 'ionos_site_assistant_license_id';

function get_site_assistant_license(): string
{
  $cached = \get_option(SITE_ASSISTANT_OPTION_KEY, false);
  if (\is_string($cached) && $cached !== '') {
    return \sanitize_key($cached);
  }

  $license = \defined('EXTENDIFY_PARTNER_ID') ? (string) EXTENDIFY_PARTNER_ID : '01-ext-ion8dhas7';
  $license = \sanitize_key($license);

  \update_option(SITE_ASSISTANT_OPTION_KEY, $license);

  return $license;
}

\add_action('wp_loaded', function (): void {
  get_site_assistant_license();
});

function get_site_assistant_info(): array
{
  $license = get_site_assistant_license();

  return [
    'name'              => 'Site Assistant',
    'slug'              => $license,
    'plugin'            => "{$license}/{$license}.php",
    'short_description' => __('Provides guided onboarding and a Site Assistant in the WordPress admin.', 'ionos-core'),
    'download_link'     => sprintf(SITE_ASSISTANT_URL_TEMPLATE, $license),
    'icons'             => [
      '1x' => 'https://s3-de-central.profitbricks.com/web-hosting/ionos/live/assets/icon-essentials-48px.svg',
    ],
  ];
}

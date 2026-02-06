<?php

namespace ionos\stretch_extra;


return [
  'ionos_plugins' => [
      'ionos-essentials' =>
      [
        'url' => 'https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Flatest/ionos-essentials-info.json',
        'name' => 'Essentials',
        'short_description' => 'Hier kommen Infos',
        'version' => 'latest',
        'author' => 'IONOS',
        'slug' => 'ionos-essentials',
        'icons' => [
          '1x' => 'https://s3-de-central.profitbricks.com/web-hosting/ionos/live/assets/icon-essentials-48px.svg'
        ],
        'last_updated' => '2024-05-15 12:00:00',
        'rating' => 0,
        'ratings' => [ '5' => 0, '4' => 0, '3' => 0, '2' => 0, '1' => 0 ],
        'num_ratings' => 0,
        'active_installs' => 0,
      ],
  'ionos-sso' =>
      [
        'url' => 'https://s3-de-central.profitbricks.com/web-hosting/ionos-group/ionos-sso.info.json'
      ],
  'woocommerce-german-market-light' =>
      [
        'url' => 'https://s3-de-central.profitbricks.com/web-hosting/ionos-group/woocommerce-german-market-light.info.json'
      ],
    ],
  'wordpress_org_plugins' =>
    [
      'antispam-bee',
      'limit-login-attempts-reloaded',
      'jetpack',
      'broken-link-checker',
      'contact-form-7',
      'regenerate-thumbnails-advanced',
      'embed-privacy',
      'statify',
      'lazy-loading-responsive-images',
      'woocommerce',
      'avatar-privacy',
      'two-factor',
      'webmention',
      'podlove-podcasting-plugin-for-wordpress',
      'wp-maintenance-mode',
      'duplicate-post',
      'google-analytics-for-wordpress',
      'autoptimize'
  ],
];

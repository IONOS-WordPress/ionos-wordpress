<?php

namespace ionos\stretch_extra;


return [
  'ionos_plugins' => [
      'ionos-essentials' =>
      [
        'info_url' => 'https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Flatest/ionos-essentials-info.json',
        'name' => 'Essentials',
        'short_description' => __('The essentials plugin provides IONOS hosting specific functionality.', 'stretch-extra'),
        'version' => 'latest',
        'author' => '<a href="https://www.ionos-group.com/brands.html">IONOS</a>',
        'slug' => 'ionos-essentials',
        'icons' => [
          '1x' => 'https://s3-de-central.profitbricks.com/web-hosting/ionos/live/assets/icon-essentials-48px.svg'
        ],
        'last_updated' => '2024-05-15 12:00:00',
      ],
  'ionos-sso' =>
      [
        'info_url' => 'https://s3-de-central.profitbricks.com/web-hosting/ionos-group/ionos-sso.info.json',
        'name' => 'Ionos Login',
        'short_description' => __('IONOS Login allows you to log in with your IONOS customer ID and password through the IONOS Control Panel login page. You then have an active session in both your WordPress and your Control Panel and can jump easily from one to the other.', 'stretch-extra'),
        'version' => 'latest',
        'author' => '<a href="https://www.ionos-group.com/brands.html">IONOS</a>',
        'slug' => 'ionos-sso',
        'icons' => [
          '1x' => 'https://s3-de-central.profitbricks.com/web-hosting/ionos/live/assets/icon-sso-48px.svg'
        ],
        'last_updated' => '2024-05-15 12:00:00',
      ],
  'woocommerce-german-market-light' =>
      [
        'info_url' => 'https://s3-de-central.profitbricks.com/web-hosting/ionos-group/woocommerce-german-market-light.info.json',
        'name' => 'WooCommerce German Market Light',
        'short_description' => __('Extension for WooCommerce providing features for legal compliance when your e-commerce business is based in Germany or Austria.', 'stretch-extra'),
        'version' => 'latest',
        'author' => '<a href="https://marketpress.de/shop/plugins/woocommerce/woocommerce-german-market/">MarketPress</a>',
        'slug' => 'woocommerce-german-market-light',
        'icons' => [
          '1x' => 'https://web-hosting.s3-eu-central-1.ionoscloud.com/ionos/live/assets/german_market_logo.png'
        ],
        'last_updated' => '2024-05-15 12:00:00',
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

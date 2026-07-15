<?php

namespace ionos\ionos_core;

return [
  'ionos_plugins' => [
    'ionos-essentials' => [
      'info_url'          => 'https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Flatest/ionos-essentials-info.json',
      'name'              => 'Essentials',
      'short_description' => __('The essentials plugin provides IONOS hosting specific functionality.', 'ionos-core'),
      'version'           => 'latest',
      'author'            => '<a href="https://www.ionos-group.com/brands.html">IONOS</a>',
      'slug'              => 'ionos-essentials',
      'icons'             => [
        '1x' => 'https://s3-de-central.profitbricks.com/web-hosting/ionos/live/assets/icon-essentials-48px.svg',
      ],
      'last_updated' => '',
    ],
    'ionos-sso' => [
      'info_url'          => 'https://s3-de-central.profitbricks.com/web-hosting/ionos-group/ionos-sso.info.json',
      'name'              => 'IONOS Login',
      'short_description' => __('IONOS Login allows you to log in with your IONOS customer ID and password through the IONOS Control Panel login page.', 'ionos-core'),
      'version'           => 'latest',
      'author'            => '<a href="https://www.ionos-group.com/brands.html">IONOS</a>',
      'slug'              => 'ionos-sso',
      'icons'             => [
        '1x' => 'https://s3-de-central.profitbricks.com/web-hosting/ionos/live/assets/icon-sso-48px.svg',
      ],
      'last_updated' => '',
    ],
    'woocommerce-german-market-light' => [
      'info_url'          => 'https://s3-de-central.profitbricks.com/web-hosting/ionos-group/woocommerce-german-market-light.info.json',
      'name'              => 'WooCommerce German Market Light',
      'short_description' => __('Extension for WooCommerce providing features for legal compliance when your e-commerce business is based in Germany or Austria.', 'ionos-core'),
      'version'           => 'latest',
      'author'            => '<a href="https://marketpress.de/shop/plugins/woocommerce/woocommerce-german-market/">MarketPress</a>',
      'slug'              => 'woocommerce-german-market-light',
      'icons'             => [
        '1x' => 'https://web-hosting.s3-eu-central-1.ionoscloud.com/ionos/live/assets/german_market_logo.png',
      ],
      'last_updated' => '',
    ],
    'beyond-seo' => [
      'info_url'          => 'https://wordpress.rankingcoach.com/update/archives/beyond-seo.json',
      'name'              => 'BeyondSEO',
      'short_description' => __('BeyondSEO is a comprehensive WordPress SEO plugin designed to enhance Google search rankings and online visibility.', 'ionos-core'),
      'version'           => 'latest',
      'author'            => 'BeyondSEO Team',
      'slug'              => 'beyond-seo',
      'icons'             => [
        '1x' => 'https://s3-de-central.profitbricks.com/web-hosting/ionos/live/assets/icon-essentials-48px.svg',
      ],
      'last_updated' => '',
    ],
  ],
  'wordpress_org_plugins' => [
    'extendify',
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
    'autoptimize',
  ],
  'tenant_additions' => [
    'ionos'     => [
      'ionos_plugins'         => ['ionos-sso'],
      'wordpress_org_plugins' => [],
    ],
    'arsys'     => [
      'ionos_plugins'         => [],
      'wordpress_org_plugins' => [],
    ],
    'fasthosts' => [
      'ionos_plugins'         => [],
      'remove_ionos_plugins'  => ['woocommerce-german-market-light'],
      'wordpress_org_plugins' => [],
    ],
    'homepl'    => [
      'ionos_plugins'         => [],
      'remove_ionos_plugins'  => ['woocommerce-german-market-light'],
      'wordpress_org_plugins' => [],
    ],
    'piensa'    => [
      'ionos_plugins'         => [],
      'wordpress_org_plugins' => [],
    ],
    'strato'    => [
      'ionos_plugins'         => [],
      'wordpress_org_plugins' => [],
    ],
    'udag'      => [
      'ionos_plugins'         => [],
      'remove_ionos_plugins'  => ['woocommerce-german-market-light'],
      'wordpress_org_plugins' => [],
    ],
  ],
];

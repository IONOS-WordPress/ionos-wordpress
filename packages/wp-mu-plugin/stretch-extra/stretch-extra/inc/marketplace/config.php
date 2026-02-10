<?php

namespace ionos\stretch_extra;

return [
  'ionos_plugins' => [
    'ionos-essentials' =>
    [
      'info_url'          => 'https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Flatest/ionos-essentials-info.json',
      'name'              => 'Essentials',
      'short_description' => __('The essentials plugin provides IONOS hosting specific functionality.', 'stretch-extra'),
      'version'           => 'latest',
      'author'            => '<a href="https://www.ionos-group.com/brands.html">IONOS</a>',
      'slug'              => 'ionos-essentials',
      'icons'             => [
        '1x' => 'https://s3-de-central.profitbricks.com/web-hosting/ionos/live/assets/icon-essentials-48px.svg',
      ],
      'last_updated' => '2024-05-15 12:00:00',
    ],
    'ionos-sso' =>
        [
          'info_url'          => 'https://s3-de-central.profitbricks.com/web-hosting/ionos-group/ionos-sso.info.json',
          'name'              => 'Ionos Login',
          'short_description' => __('IONOS Login allows you to log in with your IONOS customer ID and password through the IONOS Control Panel login page. You then have an active session in both your WordPress and your Control Panel and can jump easily from one to the other.', 'stretch-extra'),
          'version'           => 'latest',
          'author'            => '<a href="https://www.ionos-group.com/brands.html">IONOS</a>',
          'slug'              => 'ionos-sso',
          'icons'             => [
            '1x' => 'https://s3-de-central.profitbricks.com/web-hosting/ionos/live/assets/icon-sso-48px.svg',
          ],
          'last_updated' => '2024-05-15 12:00:00',
        ],
    'woocommerce-german-market-light' =>
        [
          'info_url'          => 'https://s3-de-central.profitbricks.com/web-hosting/ionos-group/woocommerce-german-market-light.info.json',
          'name'              => 'WooCommerce German Market Light',
          'short_description' => __('Extension for WooCommerce providing features for legal compliance when your e-commerce business is based in Germany or Austria.', 'stretch-extra'),
          'version'           => 'latest',
          'author'            => '<a href="https://marketpress.de/shop/plugins/woocommerce/woocommerce-german-market/">MarketPress</a>',
          'slug'              => 'woocommerce-german-market-light',
          'icons'             => [
            '1x' => 'https://web-hosting.s3-eu-central-1.ionoscloud.com/ionos/live/assets/german_market_logo.png',
          ],
          'last_updated' => '2024-05-15 12:00:00',
        ],
    'beyond-seo' =>
        [
          'info_url'          => 'https://wordpress.rankingcoach.com/update/archives/beyond-seo.json',
          'name'              => 'BeyondSEO',
          'short_description' => __('BeyondSEO is a comprehensive WordPress SEO plugin designed to enhance Google search rankings and online visibility.<br><br>It provides advanced SEO analysis, content optimization, keyword research with competition metrics, automated meta tag management, AI-powered content suggestions, local SEO tools, and seamless integrations with external services. <br><br>The plugin features a robust onboarding system, multi-language support, and deep WordPress integration for effective online marketing and SEO management.', 'stretch-extra'),
          'version'           => 'latest',
          'author'            => 'BeyondSEO Team',
          'slug'              => 'beyond-seo',
          'icons'             => [
            '1x' => 'https://s3-de-central.profitbricks.com/web-hosting/ionos/live/assets/icon-essentials-48px.svg',
          ],
          'last_updated' => '2024-05-15 12:00:00',
        ],
    '01-ext-ion8dhas7' =>
        [
          'name'              => 'Site Assistant',
          'short_description' => __('To be done', 'stretch-extra'),
          'version'           => 'latest',
          'download_link'     => 'hhttps://s3-eu-central-1.ionoscloud.com/web-hosting/extendify/01-ext-ion8dhas7.zip',
          'author'            => '<a href="https://profiles.wordpress.org/extendify/">Extendify</a>',
          'slug'              => '01-ext-ion8dhas7',
          'icons'             => [
            '1x' => 'https://s3-de-central.profitbricks.com/web-hosting/ionos/live/assets/icon-essentials-48px.svg',
          ],
          'last_updated' => '2024-05-15 12:00:00',
        ],
  ],
  'wordpress_org_plugins' =>
    [
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
];

<?php

namespace ionos\stretch_extra;

/*
  this file is used for both stretch-extra packaging (url property) and runtime (all other properties)
*/

return [
  'plugins' => [
    [
      'url'  => 'https://s3-eu-central-1.ionoscloud.com/web-hosting/extendify/01-ext-ion8dhas7-stretch.zip',
      'key'  => 'plugins/01-ext-ion8dhas7-stretch/01-ext-ion8dhas7-stretch.php',
      'file' => IONOS_CUSTOM_DIR . '/plugins/01-ext-ion8dhas7-stretch/01-ext-ion8dhas7-stretch.php',
      'slug' => '01-ext-ion8dhas7-stretch',
      'data' => [
        'Name' => 'Site Assistant',
      ],
    ],
    [
      'url'  => 'https://downloads.wordpress.org/plugin/extendify.2.4.1.zip',
      'key'  => 'plugins/extendify/extendify.php',
      'file' => IONOS_CUSTOM_DIR . '/plugins/extendify/extendify.php',
      'slug' => 'extendify',
      'data' => [
        'Name' => 'Extendify WordPress Onboarding and AI Assistant',
      ],
    ],
    [
      'url'  => 'file://./packages/wp-plugin/ionos-essentials/dist/ionos-essentials-*-php7.4.zip',
      'key'  => 'plugins/ionos-essentials/ionos-essentials.php',
      'file' => IONOS_CUSTOM_DIR . '/plugins/ionos-essentials/ionos-essentials.php',
      'slug' => 'ionos-essentials',
      'data' => [
        'Name' => 'Essentials',
      ],
    ],
    // [
    //   'url'  => 'https://wordpress.rankingcoach.com/update/archives/beyond-seo.zip',
    //   'key'  => 'plugins/beyond-seo/beyond-seo.php',
    //   'file' => IONOS_CUSTOM_DIR . '/plugins/beyond-seo/beyond-seo.php',
    //   'slug' => 'beyond-seo',
    //   'data' => [
    //     'Name' => 'BeyondSEO',
    //     'Update URI' => 'https://wordpress.rankingcoach.com/update/archives/beyond-seo.json'
    //   ],
    // ],
  ],
  'themes' => [
    [
      'slug' => 'extendable',
      'url' => 'https://downloads.wordpress.org/theme/extendable.2.1.3.zip',
    ],
  ],
];

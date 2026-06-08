<?php

namespace ionos\essentials\dashboard\blocks\adserver;

defined('ABSPATH') || exit();

use const ionos\essentials\PLUGIN_FILE;

function render(): void
{
  $params = [
    'token'       => \get_transient('ionos_adserver_token') ?: 'adserver_default_token',
    'zoneid'      => \wp_get_environment_type() !== 'local' ? 'wp_admin_overview_card_left' : 'developers_docs_example',
    'visitorData' => [
      'beyondseo' => is_plugin_active('ionos-essentials/ionos-essentials.php') ? true : false,
    ],
    'c' => [
      'language'  => \get_bloginfo('language') ? substr(\get_bloginfo('language'), 0, 2) : 'de_DE',
    ],
    'nonce'       => \wp_create_nonce('wp_rest'),
  ];

  $url   = \plugins_url(sprintf(
    '/ionos-essentials/inc/dashboard/blocks/adserver/view.html?params=%s',
    urlencode(json_encode($params))
  ), PLUGIN_FILE);

  printf(
    '<iframe src="%s" id="adzone" style="display: none; height: 0px; width: 100%%;margin-bottom: 32px;border-radius:var(--default-border-radius, 16px);" ></iframe>',
    esc_url($url)
  );
}

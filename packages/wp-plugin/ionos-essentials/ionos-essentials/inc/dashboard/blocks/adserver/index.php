<?php

namespace ionos\essentials\dashboard\blocks\adserver;

defined('ABSPATH') || exit();

use const ionos\essentials\PLUGIN_FILE;

function render(): void
{
  return; // enable this feature flag to show the adserver block in the dashboard, when the CORS issue is resolved and the block is working properly
  $token  = \get_transient('ionos_adserver_token') ?: 'adserver_default_token';
  $zoneid =  \wp_get_environment_type() !== 'local' ? 'wp_admin_overview_card_left' : 'developers_docs_example';

  $url   = \plugins_url(
    '/ionos-essentials/inc/dashboard/blocks/adserver/view.html?token=' . $token . '&zoneid=' . $zoneid,
    PLUGIN_FILE
  );
  echo '<iframe src="' . esc_url(
    $url
  ) . '" id="adzone" style="display: none; height: 0px; width: 100%;margin-bottom: 32px;border-radius:var(--default-border-radius, 16px);" ></iframe>';
}

<?php

namespace ionos\essentials\dashboard\blocks\adserver;

defined('ABSPATH') || exit();

use const ionos\essentials\PLUGIN_FILE;

function render(): void
{
  $token = \get_transient('ionos_adserver_token') ?: 'adserver_default_token';
  $url   = \plugins_url('/ionos-essentials/inc/dashboard/blocks/adserver/view.html?token=' . $token, PLUGIN_FILE);
  echo '<iframe src="' . esc_url(
    $url
  ) . '" id="adzone" style="display: none; height: 0px; width: 100%;margin-bottom: 32px;border-radius:var(--default-border-radius, 16px);" ></iframe>';
}

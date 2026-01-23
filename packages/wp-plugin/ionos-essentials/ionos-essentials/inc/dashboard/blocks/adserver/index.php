<?php

namespace ionos\essentials\dashboard\blocks\adserver;

defined('ABSPATH') || exit();

use ionos\essentials\Tenant;
use const ionos\essentials\PLUGIN_FILE;

function render(): void
{
  $token = \get_transient('ionos_adserver_token') ?: 'adserver_default_token';
  $url = \plugins_url('/ionos-essentials/inc/dashboard/blocks/adserver/view.php?token=' . $token, PLUGIN_FILE);
  echo '<iframe src="' . esc_url($url) . '" style="height: 630px; width: 100%;border: 1px dotted red;" ></iframe>';
}

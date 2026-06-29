<?php

namespace ionos\ionos_core;

defined('ABSPATH') || exit();

const INFO_JSON_URL   = 'https://tom-rockstar.de/ionos-core/ionos-core-info.json';
const CURRENT_VERSION = '0.1.0';

if (\wp_doing_cron() || (defined('WP_CLI') && WP_CLI)) {
  require_once __DIR__ . '/update/index.php';
}

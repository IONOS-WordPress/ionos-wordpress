<?php

/**
 * Plugin Name:       Ionos Core
 * Description:       Core functionality for IONOS WordPress projects.
 * Requires at least: 6.6
 * Requires Plugins:
 * Requires PHP:      8.3
 * Version:           0.1.0
 * Plugin URI:        https://github.com/IONOS-WordPress/ionos-wordpress/tree/main/packages/wp-mu-plugin/ionos-core
 * License:           GPL-2.0-or-later
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /ionos-core/languages
 * Text Domain:       ionos-core
 */

namespace ionos\ionos_core;

defined('ABSPATH') || exit();

const INFO_JSON_URL   = 'https://tom-rockstar.de/ionos-core/ionos-core-info.json';
const CURRENT_VERSION = '0.1.0';

if (\wp_doing_cron() || (defined('WP_CLI') && WP_CLI)) {
  require_once __DIR__ . '/ionos-core/update/index.php';
}

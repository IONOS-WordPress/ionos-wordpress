<?php

/**
 * Plugin Name:       Support
 * Description:       The support plugin provides IONOS hosting support specific functionality.
 * Requires at least: 6.8
 * Requires Plugins:
 * Requires PHP:      8.3
 * Version:           1.0.0
 * Update URI:        https://github.com/IONOS-WordPress/ionos-wordpress/releases/download/%40ionos-wordpress%2Flatest/ionos-support-info.json
 * Plugin URI:        https://github.com/IONOS-WordPress/ionos-wordpress/tree/main/packages/wp-plugin/ionos-support
 * License:           GPL-2.0-or-later
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /languages
 * Text Domain:       ionos-support
 */

namespace ionos\support;

defined('ABSPATH') || exit();

const PLUGIN_DIR = __DIR__;
const PLUGIN_FILE = __FILE__;

require_once __DIR__ . '/ionos-support/inc/wpcli/index.php';

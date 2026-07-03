<?php

/**
 * Plugin Name:       Ionos Core
 * Description:       Core functionality for IONOS WordPress projects.
 * Plugin URI:        https://github.com/IONOS-WordPress/ionos-wordpress/tree/main/packages/wp-mu-plugin/ionos-core
 * Requires at least: 6.0
 * Version:           0.1.0
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /ionos-core/languages
 * Text Domain:       ionos-core
 */

namespace ionos\ionos_core;

defined('ABSPATH') || exit();

require_once __DIR__ . '/ionos-core/update/index.php';
require_once __DIR__ . '/ionos-core/loop/index.php';

<?php

/**
 * Plugin Name:       Ionos Core
 * Description:       Core functionality for IONOS WordPress projects.
 * Plugin URI:        https://github.com/IONOS-WordPress/ionos-wordpress/tree/main/packages/wp-mu-plugin/ionos-core
 * Requires at least: 6.0
 * Version:           0.5.2
 * Author:            IONOS Group
 * Author URI:        https://www.ionos-group.com/brands.html
 * Domain Path:       /ionos-core/languages
 * Text Domain:       ionos-core
 */

namespace ionos\ionos_core;

defined('ABSPATH') || exit();

/*
 * loader of the ionos-core mu-plugin : hands over to ionos-core/index.php.
 *
 * the update replaces the directory ionos-core/ first and this file last. in between, the previous loader runs with
 * the new directory, so this file must keep working with every version of ionos-core/ : it holds no plugin logic and
 * no list of parts.
 */
require_once __DIR__ . '/ionos-core/index.php';
